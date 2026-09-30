<?php

declare(strict_types=1);

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;

final class CalculatorContext implements Context
{
    private int $value = 0;

    #[Given('a value of :value')]
    public function aValueOf(int $value): void
    {
        $this->value = $value;
    }

    #[When('I add :value')]
    public function iAdd(int $value): void
    {
        $this->value += $value;
    }

    #[When('I add the following values:')]
    public function iAddTheFollowingValues(TableNode $table): void
    {
        foreach ($table->getColumn(0) as $value) {
            $this->value += (int) $value;
        }
    }

    #[Then('the value should be :expected')]
    public function theValueShouldBe(int $expected): void
    {
        if ($expected !== $this->value) {
            throw new UnexpectedValueException(sprintf('Expected %d, got %d.', $expected, $this->value));
        }
    }

    #[Then('the value should be described as:')]
    public function theValueShouldBeDescribedAs(PyStringNode $text): void
    {
        if (trim($text->getRaw()) !== "value {$this->value}") {
            throw new UnexpectedValueException('Unexpected description.');
        }
    }
}
