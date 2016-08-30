#!/bin/env ruby

require "nokogiri"

# Mapping of kernel build times hash key to the content of the Description tag
# of the Result with the Title tag with the content "Full Linux Kernel Build" in
# the Phoronix test suite composite.xml file.
DESCRIPTIONS = {
  elapsed: "Real time",
  system: "Sys time",
  user: "User time",
}

TEST_RESULTS_PATH = "~/.phoronix-test-suite/test-results"

def usage
  puts <<-EOS
usage: #{File.basename($PROGRAM_NAME)} path-to-raw-data system-name [result-name]

Adds the boot time and kernel build times from the raw data path
'path-to-raw-data' to the Phoronix test suite result name 'result-name' as the
result for 'system-name'. If the 'result-name' is not specified, it defaults to
'compile-test'. All results are assumed to be in #{TEST_RESULTS_PATH} if the
'result-name' doesn't contain a path.
  EOS
  exit 0
end

def get_arguments
  path_to_raw_data = ARGV[0]
  system_name = ARGV[1]
  result_name = ARGV[2] || "compile-test"
  unless result_name.include?("/")
    result_name = File.join(TEST_RESULTS_PATH, result_name)
  end
  [path_to_raw_data, system_name, result_name]
end

# Change an array of strings to a corresponding array of time in seconds.
# Expects the string to be an elapsed time in hours:minutes:seconds where hours
# or hours and minutes are optional. For example:
# to_seconds(["30", "1:30", "1:01:30"]) => [30, 90, 3690]
def convert_to_seconds(times)
  times.map do |s|
    s.split(":")
      .reverse.map.with_index { |value, index| value.to_f * 60**index }
      .reduce(&:+)
  end
end

# The kernel-build-time file in the raw-data directory for the system config
# has lines like this:
#   8707.06user 1469.72system 50:43.99elapsed 334%CPU (0avgtext+0avgdata 569784maxresident)k
#   52880inputs+70713320outputs (4721major+489707306minor)pagefaults 0swaps
# We pull the user, system, and elapsed times out, convert them from durations
# in hours:minutes:seconds to a hash with the time in seconds
def read_kernel_build_times(path_to_raw_data)
  filename = File.join(path_to_raw_data, "kernel-build-time")
  times = File.readlines(File.expand_path(filename)).first
  user, system, elapsed = *convert_to_seconds(
    times.match("(.*)user (.*)system (.*)elapsed")[1..3])
  { user: user, system: system, elapsed: elapsed }
end

def result_filename(result_name)
  File.expand_path(File.join(result_name, "composite.xml"))
end

def read_result_file(result_name)
  Nokogiri::Slop(File.read(result_filename(result_name)))
end

def remove_systemd_kernel_and_userspace_boot_times(doc)
  s = <<-EOS.gsub("\n", "").strip
    //Result[
        Title[text()='Systemd Total Boot Time'] and
        Arguments[text()='kernel' or text()='userspace']
      ]
  EOS
  doc.search(s).each(&:remove)
end

def add_kernel_build_time_entry(doc, description, entry)
    s = <<-EOS.gsub("\n", "").strip
      //Result[
          Title[text()='Full Linux Kernel Build'] and
          Description[text()='#{description}']
        ]/Data/Entry
    EOS
    doc.search(s).last.add_next_sibling(entry)
end

def new_entry(identifier, value)
  <<-EOS.chomp

      <Entry>
        <Identifier>#{identifier}</Identifier>
        <Value>#{value}</Value>
        <RawString></RawString>
      </Entry>
  EOS
end

def add_kernel_build_times(doc, system_name, build_times)
  build_times.each do |key, time|
    description = DESCRIPTIONS[key]
    add_kernel_build_time_entry(doc, description, new_entry(system_name, time))
  end
end

def write_result_file(result_name, doc)
  doc.write_to(File.open(result_filename(result_name), "w"), &:no_empty_tags)
end


usage if ARGV.length != 2 and ARGV.length != 3

path_to_raw_data, system_name, result_name = *get_arguments
kernel_build_times = read_kernel_build_times(path_to_raw_data)
doc = read_result_file(result_name)
add_kernel_build_times(doc, system_name, kernel_build_times)
remove_systemd_kernel_and_userspace_boot_times(doc)
write_result_file(result_name, doc)
