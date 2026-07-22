@mod @mod_certmanager
Feature: Certification Manager activity
  In order to issue and track certifications
  As a teacher
  I need to create Certification Manager activities and let students view them

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
      | student1 | Student   | One      | student1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |

  @javascript
  Scenario: Teacher adds a Certification Manager activity to a course
    Given I am on the "Course 1" course page logged in as teacher1
    And I turn editing mode on
    When I add a "Certification Manager" to section "1" and I fill the form with:
      | Name | Compliance certification |
    Then I should see "Compliance certification"

  Scenario: Student can open a Certification Manager activity
    Given the following "activities" exist:
      | activity    | name                     | intro                | course | idnumber |
      | certmanager | Compliance certification | Certification intro. | C1     | cm1      |
    When I am on the "Compliance certification" "certmanager activity" page logged in as student1
    Then I should see "Compliance certification"
