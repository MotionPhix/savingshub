<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateGroupRequest extends FormRequest
{
  public function rules()
  {
    return [
      'name' => 'required|string|max:255|unique:groups,name',
      'start_date' => 'required|date|after:' . now()->addDay()->format('Y-m-d'),
      'end_date' => [
        'required', 'date'
      ],
      'description' => 'nullable|string|max:1000',
      'contribution_frequency' => 'required|in:weekly,monthly,quarterly,annually',
      'contribution_amount' => 'required|numeric|min:1',
      'duration_months' => 'required|integer|min:1|max:36',
      'loan_interest_type' => 'required|in:fixed,variable,tiered',
      'base_interest_rate' => 'required|numeric|min:0|max:100',
      'max_loan_amount' => 'nullable|numeric|min:0',
      'require_group_approval' => 'boolean',

      // Specific validation for tiered interest rates
      'interest_tiers' => [
        Rule::requiredIf($this->input('loan_interest_type') === 'tiered'),
        'array',
        function ($attribute, $value, $fail) {
          // Validate tiers structure and logic
          if ($this->input('loan_interest_type') === 'tiered') {
            $this->validateInterestTiers($value, $fail);
          }
        }
      ],
      'interest_tiers.*.min_amount' => 'required|numeric|min:0',
      'interest_tiers.*.max_amount' => 'required|numeric|gt:min_amount',
      'interest_tiers.*.rate' => 'required|numeric|min:0|max:100',
    ];
  }

  protected function validateInterestTiers(array $tiers, $fail)
  {
    // Ensure at least one tier
    if (empty($tiers)) {
      $fail('At least one interest tier is required.');
      return;
    }

    // Sort tiers by min_amount to ensure proper sequencing
    usort($tiers, function ($a, $b) {
      return $a['min_amount'] <=> $b['min_amount'];
    });

    // Validate tier sequence and overlaps
    foreach ($tiers as $index => $tier) {
      // Check first tier starts at 0
      if ($index === 0 && $tier['min_amount'] !== 0) {
        $fail('First tier must start at 0.');
        return;
      }

      // Check sequential tiers
      if ($index > 0) {
        $prevTier = $tiers[$index - 1];

        // Ensure no gaps between tiers
        if ($tier['min_amount'] !== $prevTier['max_amount'] + 1) {
          $fail('Tiers must be sequential without gaps.');
          return;
        }
      }

      // Validate rate is positive
      if ($tier['rate'] <= 0) {
        $fail('Interest rate must be greater than 0.');
        return;
      }
    }
  }

  public function messages()
  {
    return [
      'interest_tiers.required' => 'Interest tiers are required for tiered interest type.',
      'interest_tiers.*.min_amount.required' => 'Minimum amount is required for each tier.',
      'interest_tiers.*.max_amount.required' => 'Maximum amount is required for each tier.',
      'interest_tiers.*.rate.required' => 'Interest rate is required for each tier.',
    ];
  }
}
