<?php

namespace Beike\Shop\Services\TotalServices;

use Beike\Shop\Services\CheckoutService;

class CustomFeeService
{
    const CUSTOMS_FEE   = 2000;
    const TRANSPORT_FEE = 7000;

    public static function getTotal(CheckoutService $checkout): array
    {
        $totalService = $checkout->totalService;

        $fees = [
            [
                'code'        => 'customs_fee',
                'title'       => 'Хятадын гааль бүрдүүлэлт',
                'description' => 'Хятадын гаалийн байгууллагаар бараа нэвтрүүлэх үйлчилгээний хөлс',
                'icon_type'   => 'customs',
                'amount'      => self::CUSTOMS_FEE,
            ],
            [
                'code'        => 'transport_fee',
                'title'       => 'Тээвэр',
                'description' => 'Хятадаас Монгол хүртэлх тээвэр — нэг машины ачааны үнэ',
                'icon_type'   => 'transport',
                'amount'      => self::TRANSPORT_FEE,
            ],
        ];

        $feeTotal = 0;
        foreach ($fees as $fee) {
            $totalData = [
                'code'          => $fee['code'],
                'title'         => $fee['title'],
                'description'   => $fee['description'],
                'icon_type'     => $fee['icon_type'],
                'amount'        => $fee['amount'],
                'amount_format' => currency_format($fee['amount']),
            ];
            $totalService->amount  += $fee['amount'];
            $totalService->totals[] = $totalData;
            $feeTotal              += $fee['amount'];
        }

        $totalService->totals[] = [
            'code'          => 'transport_subtotal',
            'title'         => 'Нийт тээвэрлэлтийн дүн',
            'amount'        => $feeTotal,
            'amount_format' => currency_format($feeTotal),
            'is_subtotal'   => true,
        ];

        return $totalService->totals;
    }
}
