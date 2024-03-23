@customfield @customfield_training @local @local_catalogue @openlms
Feature: Local course catalogue training filtering

  Background:
    Given I skip tests if "local_catalogue" is not installed
    And the following "categories" exist:
      | name  | category | idnumber |
      | Cat 1 | 0        | CAT1     |
      | Cat 2 | 0        | CAT2     |
    And the following "custom field categories" exist:
      | name              | component   | area   | itemid |
      | Category for test | core_course | course | 0      |
    And the following "custom fields" exist:
      | name        | category           | type     | shortname | description | configdata            |
      | TrainingF 1 | Category for test  | training | training1 | tf1         |                       |
      | TrainingF 2 | Category for test  | training | training2 | tf2         |                       |
    And the following "courses" exist:
      | fullname      | idnumber | shortname | category | customfield_training1 | customfield_training2 |
      | Test Course 1 | CO1      | COURSE1   | CAT1     | 21                    | 10                    |
      | Test Course 2 | CO2      | COURSE2   | CAT1     | 22                    |                       |
      | Science       | S1       | SC        | CAT2     |                       | 1                     |

  @javascript
  Scenario: Search the catalogue for courses by training
    Given I log in as "admin"
    And I am on the "local_catalogue > Course catalogue" page
    And I should see "Test Course 1"
    And I should see "Test Course 2"
    And I should see "Science"

    And I click on ".show-more" "css_element"
    When I set the following fields to these values:
      |  TrainingF 1 | Yes |
    And I press "Search"
    Then I should see "Test Course 1"
    And I should see "Test Course 2"
    And I should not see "Science"

    And I click on ".show-more" "css_element"
    When I set the following fields to these values:
      |  TrainingF 1 | Yes |
      |  TrainingF 2 | No |
    And I press "Search"
    Then I should not see "Test Course 1"
    And I should see "Test Course 2"
    And I should not see "Science"
