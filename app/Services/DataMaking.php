<?php
namespace App\Services;

class DataMaking
{
    public function storeCountRng()
    {
        $allStore= 122;

        $activeStoreCount= round($allStore*mt_rand(60, 100)/100);
        $stockInStoreCount= round($activeStoreCount*mt_rand(60, 100)/100);
        $salesStoreCount= round($allStore*mt_rand(90, 100)/100);
        $activeStoreRatePct= $activeStoreCount/$allStore;
        $stockInStoreRatePct= $stockInStoreCount/$allStore;

        return [
            (int) $activeStoreCount,
            (int) $stockInStoreCount,
            (int) $salesStoreCount,
            (float) $activeStoreRatePct,
            (float) $stockInStoreRatePct,
        ];
    }

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
        $salesAmountYoy = $salesAmount / $salesAmountLy;
        $salesAmountMix = mt_rand(1111, 26999) / 10000;

        return [
            (int) $salesAmount,
            (int) $salesAmountLy,
            (int) $salesAmountDiff,
            (float) $salesAmountYoy,
            (float) $salesAmountMix,
        ];
    }

    public function QuantityRng($Tier)
    {
        $min= 1;
        $max= 1;

        switch($Tier){
            case 't1':
                $min= 5000;
                $max= 8000;
                break;

            case 't2':
                $min= 4000;
                $max= 6000;
                break;

            case 't3':
                $min= 3000;
                $max= 4500;
                break;

            case 't4':
                $min= 2000;
                $max= 3500;
                break;

            case 't5':
                $min= 500;
                $max= 3000;
                break;

        }

        $stockInQuantity= mt_rand($min, $max); //進貨數量

        $diffRng= mt_rand(10, 160);
        $diffRate= $diffRng/ 1000;

        if($diffRng % 4 == 0 && $diffRng < 45){
            $salesQuantityDiff = round($stockInQuantity * $diffRate * -1) ;
        }else{
            $salesQuantityDiff = round($stockInQuantity * $diffRate) ;
        }

        $stockInQuantityLy = $stockInQuantity - $salesQuantityDiff; //進貨數量_前年實績
        $salesQuantity= round($stockInQuantity*mt_rand(9200, 10000)/10000); //銷售數量
        $salesQuantityDiff= round($salesQuantity*mt_rand(100, 1500)/10000); //銷售數量_前年差
        $salesQuantityYoyPct= $salesQuantity/($salesQuantity-$salesQuantityDiff); //銷售數量_前年比%
        $wasteQuantity= round(($stockInQuantity-$salesQuantity)*mt_rand(3000, 9000)/10000); //廢棄數量
        $wasteQuantityLy= round($wasteQuantity*mt_rand(4000, 9000)/10000); //廢棄數量_前年實績
        $returnQuantity= round(($stockInQuantity-$salesQuantity-$wasteQuantity)*mt_rand(3000, 9000)/10000); //退貨數量
        $returnQuantityLy= round($returnQuantity*mt_rand(7000, 12000)/10000); //退貨數量_前年實績
        $transferQuantity= round($wasteQuantity*mt_rand(10000, 20000)/10000); //轉貨數量
        $transferQuantityLy= round($transferQuantity*mt_rand(7000, 12000)/10000); //轉貨數量_前年實績



        return [
            (int) $stockInQuantity,
            (int) $stockInQuantityLy,
            (int) $salesQuantity,
            (int) $salesQuantityDiff,
            (float) $salesQuantityYoyPct,
            (int) $wasteQuantity,
            (int) $wasteQuantityLy,
            (int) $returnQuantity,
            (int) $returnQuantityLy,
            (int) $transferQuantity,
            (int) $transferQuantityLy

        ];
    }

}



?>
