#!/usr/bin/env ruby

require "nokogiri"
require "ostruct"

CONFIGS_FILTER = /z840|ZFS|8570w/

filename = ARGV[1] || "test-results/full-results/composite.xml"
doc = Nokogiri::Slop(File.read(filename))

# configs is a hash where keys are the config name (e.g., "SSD MBT XFS") and
# the values are a hash of the test results where the key is the name of the
# test and the value is the benchmark result for that test. For example:
#   configs["SSD MBT XFS"]["Compile Bench/Test: Compile] = 10.0
#   configs["SSD MBT XFS"]["Compile Bench/Initial Create] = 20.0
#   configs["4 HDD LVM EXT4"]["Compile Bench/Test: Compile] = 30.0

configs = {}

# tests is a hash where the key is the name of the test (e.g., "Compile
# Bench/Test: Compile") and the value is a struct which has the properties
# higher_is_better, a boolean which indicates if a higher number is better, and
# scale, a string with the units of the result, (e.g., "MB/s"). For example:
#   tests["Compile Bench/Test: Compile"].higher_is_better = True
#   tests["Compile Bench/Test: Compile"].scale = "MB/s"
#   tests["Compile Bench/Test: Initial Create"].higher_is_better = True

tests = {}

doc.PhoronixTestSuite.System.each do |node|
  config_name = node.Identifier.content
  configs[config_name] = {} unless config_name =~ CONFIGS_FILTER
end

doc.PhoronixTestSuite.Result.each do |result|
  name = [result.Title.content, result.Description.content].join("/")
  higher_is_better = result.Proportion.content == "HIB"
  scale = result.Scale.content
  tests[name] = OpenStruct.new(higher_is_better: higher_is_better, scale: scale)

  result.Data.Entry.map do |test_result|
    config_name = test_result.Identifier.content
    value = test_result.Value.content.to_f
    configs[config_name].merge!(name => value) unless config_name =~ CONFIGS_FILTER
  end

  # Normalize all the results for a given test by offsetting the data so the
  # lowest result is 0 and then scale the data so the highest result is 1.0.

  data = configs.values.collect { |test_result| test_result[name] }

  minimum, maximum = *data.minmax
  scale = 1.0 / (maximum - minimum)

  configs.each_value do |test_result|
    test_result[name] -= minimum
    test_result[name] *= scale
  end

  # If the test is one in which a lower result is better, invert the
  # results so the lowest result is 1 and the highest result is 0.

  unless higher_is_better
    configs.each_value do |test_result|
      test_result[name] = 1.0 - test_result[name]
    end
  end
end

print "'Test', "
test_names = tests.keys
puts test_names.map { |name| "'#{name}'" }.join(", ")

configs.each do |config, data|
  print "'#{config}', "
  s = test_names.map do |test_name|
    "'%.2f'" % data[test_name]
  end.join(", ")
  print s
  puts
end
