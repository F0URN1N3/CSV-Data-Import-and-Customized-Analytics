<?php
namespace App\Services;

class DataMaking
{

    public function salesAmountRng($Tier)
    {
        $min= 1;
        $max= 1;

        switch($Tier){
            case 't1':
                $min= 700000;
                $max= 1000000;
                break;

            case 't2':
                $min= 500000;
                $max= 800000;
                break;

            case 't3':
                $min= 300000;
                $max= 600000;
                break;

            case 't4':
                $min= 100000;
                $max= 400000;
                break;

            case 't5':
                $min= 50000;
                $max= 200000;
                break;

            default: return [20000, 10001, 9999, 0.9, 0.9];
        }

        $salesAmount= mt_rand($min, $max);

        $diffRng= mt_rand(10, 160);

        $diffRate= $diffRng/ 1000;

        if($diffRng % 4 == 0 && $diffRng < 45){
            $salesAmountDiff = round($salesAmount * $diffRate * -1) ;
        }else{
            $salesAmountDiff = round($salesAmount * $diffRate) ;
        }

        $salesAmountLy = $salesAmount - $salesAmountDiff;
        $salesAmountYoy = ($salesAmount - $salesAmountLy) / $salesAmountLy;
        $salesAmountMix = mt_rand(1111, 26999) / 10000;

        return [
            (int) $salesAmount,
            (int) $salesAmountLy,
            (int) $salesAmountDiff,
            (float) $salesAmountYoy,
            (float) $salesAmountMix,
        ];
    }

    public function salesQuantityRng($Tier)
    {
        $min= 1;
        $max= 1;

        switch($Tier){
            case 't1':
                $min= 2000;
                $max= 3000;
                break;

            case 't2':
                $min= 1700;
                $max= 2700;
                break;

            case 't3':
                $min= 1300;
                $max= 2300;
                break;

            case 't4':
                $min= 800;
                $max= 1800;
                break;

            case 't5':
                $min= 300;
                $max= 1200;
                break;

            default: return [200, 100, 100, 0.9, 0.9];
        }

        $salesQuantity= mt_rand($min, $max);

        $diffRng= mt_rand(10, 160);

        $diffRate= $diffRng/ 1000;

        if($diffRng % 4 == 0 && $diffRng < 45){
            $salesQuantityDiff = round($salesQuantity * $diffRate * -1) ;
        }else{
            $salesQuantityDiff = round($salesQuantity * $diffRate) ;
        }

        $salesQuantityLy = $salesQuantity - $salesQuantityDiff;
        $salesQuantityYoy = ($salesQuantity - $salesQuantityLy) / $salesQuantityLy;
        $salesQuantityMix = mt_rand(11, 16999) / 10000;

        return [
            (int) $salesQuantity,
            (int) $salesQuantityLy,
            (int) $salesQuantityDiff,
            (float) $salesQuantityYoy,
            (float) $salesQuantityMix,
        ];
    }

}



?>
