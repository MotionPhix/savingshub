<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GroupSettingsRequest extends FormRequest
{
  public function authorize(): bool
  {
    // Ensure only group admin or creator can update settings
    $group = $this->route('group');
    return $this->user()->can('update', $group);
  }

  public function rules(): array
  {
    return [
      'settings' => 'sometimes|array',
      'settings.currency' => [
        'sometimes',
        'string',
        Rule::in(['USD', 'EUR', 'GBP', 'MWK', 'ZAR'])
      ],
      'notification_preferences' => 'sometimes|array',
      'notification_preferences.contribution_reminder' => 'sometimes|boolean',
      'notification_preferences.loan_approval' => 'sometimes|boolean',
      'notification_preferences.group_activity' => 'sometimes|boolean',
      'notification_preferences.monthly_summary' => 'sometimes|boolean',

      // Group visibility and invitation settings
      'is_public' => 'sometimes|boolean',
      'allow_member_invites' => 'sometimes|boolean',

      // Contribution settings
      'contribution_frequency' => [
        'sometimes',
        Rule::in(['weekly', 'monthly', 'quarterly', 'annually'])
      ],
      'contribution_amount' => 'sometimes|numeric|min:0',
    ];
  }

  public function messages(): array
  {
    return [
      'settings.currency.in' => 'Invalid currency selection',
      'contribution_frequency.in' => 'Invalid contribution frequency',
    ];
  }
}
