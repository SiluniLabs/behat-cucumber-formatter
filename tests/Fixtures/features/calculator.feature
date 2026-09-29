@calculator
Feature: Calculator
    In order to count
    As a user
    I need to add values

    Background:
        Given a value of 1

    @PROJ-1 @smoke
    Scenario: Passing scenario
        When I add 2
        Then the value should be 3
        And the value should be described as:
            """
            value 3
            """

    @PROJ-2
    Scenario: Failing scenario
        When I add 2
        Then the value should be 4
        And the value should be 3

    Scenario: Undefined step
        When I multiply by 2

    Scenario: Table argument
        When I add the following values:
            | 2 |
            | 3 |
        Then the value should be 6

    @PROJ-3
    Scenario Outline: Outline scenario
        When I add <added>
        Then the value should be <expected>

        Examples:
            | added | expected |
            | 1     | 2        |
            | 4     | 5        |
