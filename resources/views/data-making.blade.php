<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>月度模擬銷售報告</title>
    </head>
<body>

    <h1>模擬銷售報告生成器</h1>

    <form method="POST" action="{{ route('data-making.generate') }}">
        @csrf
        <label for="tier">請輸入銷售等級 (Tier: t1 ~ t5):</label>
        <input type="text" id="tier" name="tier" value="{{ old('tier', $inputTier ?? 't1') }}" required>
        <button type="submit">生成報告</button>

        @error('tier')
            <div style="color: red;">{{ $message }}</div>
        @enderror
    </form>

    <hr>

    @isset($reportData)
        <h2>報告結果 (使用等級: {{ $inputTier }})</h2>

        <table border="1" cellpadding="10" cellspacing="0" style="width: 100%;">
            <thead>
                <tr>
                    @foreach ($reportData['month'] as $month)
                        <th>{{ $month }} 月</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($reportData as $key => $values)
                    @php
                        // 定義指標的顯示名稱和格式，用於美化輸出
                        $label = match ($key) {
                            'month' => '月份',
                            'manual_input' => '手動輸入',
                            'salesAmount' => '銷售金額 (本期)',
                            'salesAmountLy' => '去年同期金額 (LY)',
                            'salesAmountDiff' => '金額差異',
                            'salesAmountYoy' => '金額年增長率 (%)',
                            'salesAmountMix' => '金額 Mix',
                            'salesQuantity' => '銷售數量 (本期)',
                            'salesQuantityLy' => '去年同期數量 (LY)',
                            'salesQuantityDiff' => '數量差異',
                            'salesQuantityYoy' => '數量年增長率 (%)',
                            'salesQuantityMix' => '數量 Mix',
                            default => $key,
                        };

                        // 定義哪些欄位需要特殊格式 (例如百分比)
                        $isPercentage = str_ends_with($key, 'Yoy');
                        $isFloat = str_ends_with($key, 'Mix') || $isPercentage;
                    @endphp

                    {{-- 排除 'month' 鍵，因為它已經在表頭顯示 --}}
                    @if ($key !== 'month')
                    <tr>
                        {{-- 橫向顯示 12 個月的數據 --}}
                        @foreach ($values as $value)
                            <td style="text-align: right;">
                                @if ($key === 'manual_input')
                                    {{ $value }}
                                @elseif ($isPercentage)
                                    {{ number_format($value * 100, 2) }}%
                                @elseif ($isFloat)
                                    {{ number_format($value, 2) }}%
                                @else
                                    {{ number_format($value) }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    @endisset

</body>
</html>
