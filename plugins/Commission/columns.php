<?php



return [
    [
        'name'            => 'rate1',
        'label_key'       => 'commission.rate1_label',
        'type'            => 'string',
        'required'        => true,
        'rules'           => 'required',
        'description_key' => 'commission.rate1_description',
    ],
    [
        'name'            => 'rate2',
        'label_key'       => 'commission.rate2_label',
        'type'            => 'string',
        'required'        => true,
        'rules'           => 'required',
        'description_key' => 'commission.rate2_description',
    ],
    [
        'name'            => 'rate3',
        'label_key'       => 'commission.rate3_label',
        'type'            => 'string',
        'required'        => true,
        'rules'           => 'required',
        'description_key' => 'commission.rate3_description',
    ],
    [
        'name'            => 'reg_join',
        'label_key'       => 'commission.reg_join_label',
        'type'            => 'select',
        'options'         => [
            [
                'value'     => '1',
                'label_key' => 'commission.reg_join_open'
            ],
            [
                'value'     => '0',
                'label_key' => 'commission.reg_join_close'
            ],
        ],
        'required'        => true,
        'description_key' => 'commission.reg_join_description',
    ],
    [
        'name'            => 'cid_days',
        'label_key'       => 'commission.cid_days_label',
        'type'            => 'string',
        'required'        => true,
        'rules'           => 'required',
        'description_key' => 'commission.cid_days_description',
    ],
    [
        'name'            => 'init_balance',
        'label_key'       => 'commission.init_balance_label',
        'type'            => 'string',
        'required'        => true,
        'rules'           => 'required',
        'description_key' => 'commission.init_balance_description',
    ],
    [
        'name'            => 'withdrawal_btn_display',
        'label_key'       => 'commission.withdrawal_btn_display_label',
        'type'            => 'select',
        'options'         => [
            [
                'value'     => '1',
                'label_key' => 'commission.withdrawal_btn_display_show'
            ],
            [
                'value'     => '0',
                'label_key' => 'commission.withdrawal_btn_display_hide'
            ],
        ],
        'required'        => true,
        'description_key' => 'commission.withdrawal_btn_display_description',
    ],
    [
        'name'            => 'cash_limit',
        'label_key'       => 'commission.cash_limit_label',
        'type'            => 'string',
        'required'        => true,
        'rules'           => 'required',
        'description_key' => 'commission.cash_limit_description',
    ],
    [
        'name'            => 'settlement_period',
        'label_key'       => 'commission.settlement_period_label',
        'type'            => 'string',
        'required'        => true,
        'rules'           => 'string',
        'description_key' => 'commission.settlement_period_description',
    ],
    [
        'name'            => 'add_balance_type',
        'label_key'       => 'commission.add_balance_type_label',
        'type'            => 'select',
        'options'         => [
            [
                'value'     => '0',
                'label_key' => 'commission.add_balance_type_commission'
            ],
            [
                'value'     => '1',
                'label_key' => 'commission.add_balance_type_wallet'
            ],
        ],
        'required'        => true,
        'description_key' => 'commission.add_balance_type_description',
    ],
];
