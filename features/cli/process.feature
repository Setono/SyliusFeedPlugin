@cli
Feature: Process feeds
  In order to generate feeds
  As a user
  I want to run the process feeds command

  Background:
    Given the store operates on a single channel in "United States"
    And the store has a product "Cold beer" with code "COLD_BEER"
    And the description of product "Cold beer" is "An ice cold beer"
    And the store also has a product "Warm beer" with code "WARM_BEER"
    And the description of product "Warm beer" is "A good warm beer"

  Scenario: Processing a feed
    Given there is a feed with feed type "google_shopping"
    When I run the process command
    Then the command should run successfully
    And a file should exist with the right content
