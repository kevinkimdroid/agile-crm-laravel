<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Client extends Model
{
    use HasFactory;

    public const SYSTEMS = [
        'individual' => 'Individual Life',
        'group' => 'Group Life',
        'mortgage' => 'Mortgage',
        'group_pension' => 'Group Pension',
    ];

    public const STATUSES = [
        'A' => 'Active',
        'FL' => 'Lapsed',
    ];

    /**
     * Kenya Orient Life product catalogue, grouped by product class.
     *
     * @see https://www.orientlife.co.ke/Products
     */
    public const PRODUCTS = [
        'Individual' => [
            'Orient Endowment Plan',
            'Orient Educator',
            'Orient Endowment',
            'Orient 4 Life',
            'Orient Smart Asset',
            'Jipange Smart',
            'Orient Smart Educator',
        ],
        'Group' => [
            'Group Last Expense',
            'Orient Group Mortgage',
            'Orient Group Life',
            'Group Credit',
        ],
        'Pension' => [
            'Orient Personal Retirement Plan',
            'Orient Umbrella Plan',
        ],
        'Annuities' => [
            'Orient Smart Annuity',
        ],
    ];

    /** Flat list of all product names. */
    public static function productNames(): array
    {
        return array_merge(...array_values(self::PRODUCTS));
    }

    /** Common occupations offered on the client capture form. */
    public const OCCUPATIONS = [
        'Teacher',
        'Farmer',
        'Business Owner',
        'Civil Servant',
        'Engineer',
        'Medical Practitioner',
        'Accountant',
    ];

    protected $fillable = [
        'policy_no',
        'first_name',
        'last_name',
        'id_no',
        'kra_pin',
        'date_of_birth',
        'gender',
        'email',
        'phone',
        'address',
        'city',
        'postal_code',
        'occupation',
        'product',
        'intermediary',
        'system',
        'status',
        'notes',
        'created_by',
        'created_by_name',
        'source',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    public static function tableExists(): bool
    {
        try {
            return Schema::hasTable('clients');
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function fullName(): string
    {
        return trim(($this->first_name ?? '').' '.($this->last_name ?? '')) ?: ($this->policy_no ?? 'Client');
    }

    /** Generate a unique local policy number (prefixed so it is easy to spot). */
    public static function generatePolicyNo(): string
    {
        do {
            $candidate = 'CRM'.now()->format('ymd').str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        } while (static::where('policy_no', $candidate)->exists());

        return $candidate;
    }

    /**
     * Shape this local client like an ERP client row so it renders in the Clients table.
     */
    public function toErpRowArray(): array
    {
        return [
            'policy' => $this->policy_no,
            'policy_no' => $this->policy_no,
            'policy_number' => $this->policy_no,
            'life_assur' => $this->fullName(),
            'firstname' => $this->first_name,
            'lastname' => $this->last_name,
            'id_no' => $this->id_no ?: '—',
            'phone_no' => $this->phone ?: '—',
            'intermediary' => $this->intermediary ?: '—',
            'pol_prepared_by' => $this->created_by_name ?: '—',
            'product' => $this->product ?: '—',
            'life_system' => $this->system,
            'status' => $this->status ?: 'A',
            'email' => $this->email ?: '—',
            'mobile' => $this->phone ?: '—',
            'is_erp' => false,
            'is_local' => true,
        ];
    }

    public function toErpRowObject(): object
    {
        $o = (object) $this->toErpRowArray();
        $o->_erp_source = true; // so the Clients table renders it as a policy row
        $o->_local_client = true;

        return $o;
    }
}
