@cli
Feature: Process feeds
  In order to generate feeds
  As a user
  I want to run the process feeds command

  Background:
    Given the store operates on a channel named "United States" with hostname "example.com"
    And the store also operates on a channel named "Denmark" with hostname "example.dk"
    And there is product "Cold beer" available in "United States" channel
    And this product has an image "cold-beer.jpg" with "main" type
    And the description of product "Cold beer" is "An ice cold beer"
    And there is product "Large beer" available in "United States" channel
    And this product has an image "large-beer.jpg" with "main" type
    And the description of product "Large beer" is "A large beer"
    And this product has been disabled
    And there is product "Warm beer" available in "Denmark" channel
    And this product has an image "warm-beer.jpg" with "main" type
    And the description of product "Warm beer" is "A good warm beer"

  Scenario: Processing a feed
    Given there is a feed with feed type "google_shopping"
    When I run the process command
    Then the command should run successfully
    And two files should exist with the right content
