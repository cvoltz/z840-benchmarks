#!/usr/bin/env ruby

require "nokogiri"
require "ostruct"

filename = ARGV[1] || "test-results/full-results/composite.xml"
doc = Nokogiri::Slop(File.read(filename))

config = doc.PhoronixTestSuite.System.reduce({}) do |config, node|
  config.merge(node.Identifier.content => 0)
end

doc.PhoronixTestSuite.Result.each do |result|
  name = [result.Title.content, result.Description.content].join("/")
  higher_is_better = result.Proportion.content == "HIB"
  scale = result.Scale.content
  data = result.Data.Entry.map do |test_result|
    OpenStruct.new(config: test_result.Identifier.content,
                   value: test_result.Value.content.to_f)
  end.sort_by { |datum| datum.value }

  if higher_is_better
    data = data.reverse_each
    text = "higher"
  else
    text = "lower"
  end

  puts "#{name} (#{scale}), #{text} is better"
  data.each do |datum|
    puts "  %8.2f: %s" % [datum.value, datum.config]
    config[datum.config] += data.find_index(datum)
  end
  puts
end

config.sort_by(&:last).each do |config|
  id, score = *config
  puts "%3d: %s" % [score, id]
end
