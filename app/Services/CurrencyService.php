<?php
namespace App\Services;

use App\Models\Group;
use Illuminate\Support\Facades\Session;

class CurrencyService
{
  // Supported currencies with their details
  private const SUPPORTED_CURRENCIES = [
    'USD' => ['symbol' => '$', 'name' => 'US Dollar', 'code' => 'USD'],
    'EUR' => ['symbol' => '€', 'name' => 'Euro', 'code' => 'EUR'],
    'GBP' => ['symbol' => '£', 'name' => 'British Pound', 'code' => 'GBP'],
    'MWK' => ['symbol' => 'K', 'name' => 'Malawi Kwacha', 'code' => 'MWK'],
    'ZAR' => ['symbol' => 'R', 'name' => 'South African Rand', 'code' => 'ZAR'],
  ];

  public function getGroupCurrency(Group $group): array
  {
    // Get currency from group settings, default to USD
    $currencyCode = $group->settings['currency'] ?? 'MWK';

    return $this->getCurrencyDetails($currencyCode);
  }

  public function getCurrencyDetails(string $currencyCode): array
  {
    return self::SUPPORTED_CURRENCIES[$currencyCode]
      ?? self::SUPPORTED_CURRENCIES['MWK'];
  }

  public function formatCurrency(
    float $amount,
    Group $group = null,
    string $currencyCode = null
  ): string {
    // Priority:
    // 1. Passed currency code
    // 2. Group's currency
    // 3. Default USD
    if ($currencyCode) {
      $currency = $this->getCurrencyDetails($currencyCode);
    } elseif ($group) {
      $currency = $this->getGroupCurrency($group);
    } else {
      $currency = $this->getCurrencyDetails('MWK');
    }

    return $this->formatCurrencyWithDetails($amount, $currency);
  }

  private function formatCurrencyWithDetails(float $amount, array $currency): string
  {
    return sprintf(
      '%s%s',
      $currency['symbol'],
      number_format($amount, 2, '.', ',')
    );
  }

  // Check if group needs currency configuration
  public function groupNeedsCurrencyConfiguration(Group $group): bool
  {
    return !isset($group->settings['currency']);
  }
}
