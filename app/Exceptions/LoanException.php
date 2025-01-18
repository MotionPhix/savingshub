<?php

namespace App\Exceptions;

use Exception;

class LoanException extends \Exception
{
  // Custom loan-related exceptions
  public static function insufficientContributions()
  {
    return new static("Insufficient contributions to qualify for a loan");
  }

  public static function activeLoanExists()
  {
    return new static("An active loan already exists");
  }

  public static function exceedsMaxLoanAmount()
  {
    return new static("Loan amount exceeds maximum allowed");
  }

  public static function invalidLoanStatus($currentStatus)
  {
    return new static("Invalid operation for loan with status: {$currentStatus}");
  }

  public static function invalidInterestTiers(string $message)
  {
    return new static("Interest Tier Error: {$message}");
  }

  public static function invalidLoanAmount(string $message)
  {
    return new static("Invalid Loan Amount: {$message}");
  }
}

