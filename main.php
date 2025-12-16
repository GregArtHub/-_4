<?php

class InvestmentOptimizer
{
    /**
     * Одномерное ДП (задача о рюкзаке) для акций
     * 
     * @param array $stocks Массив акций в формате [[стоимость, прибыль], ...]
     * @param float $budget Бюджет для инвестиций
     * @return array [максимальная прибыль, выбранные акции]
     */
    public function max_profit_1d(array $stocks, float $budget): array
    {
        $n = count($stocks);
        $dp = array_fill(0, (int)$budget + 1, 0);
        $selected = array_fill(0, (int)$budget + 1, []);
        
        for ($i = 0; $i < $n; $i++) {
            $cost = $stocks[$i][0];
            $profit = $stocks[$i][1];
            
            for ($k = (int)$budget; $k >= $cost; $k--) {
                if ($dp[$k] < $dp[$k - $cost] + $profit) {
                    $dp[$k] = $dp[$k - $cost] + $profit;
                    $selected[$k] = array_merge($selected[$k - $cost], [$i]);
                }
            }
        }
        
        // Находим максимальную прибыль (не обязательно использовать весь бюджет)
        $maxProfit = max($dp);
        $maxIndex = array_search($maxProfit, $dp);
        $selectedStocks = $selected[$maxIndex];
        
        return [$maxProfit, $selectedStocks];
    }
    
    /**
     * Двумерное ДП для оптимизации риска и доходности
     * 
     * @param array $stocks Массив акций
     * @param float $bondYield Доходность облигаций (например, 0.05 для 5%)
     * @param float $totalBudget Общий бюджет
     * @param float $riskLimit Максимальная доля в акциях (например, 0.5 для 50%)
     * @return array [прибыль, сумма в акциях, сумма в облигациях]
     */
    public function max_profit_2d(array $stocks, float $bondYield, float $totalBudget, float $riskLimit): array
    {
        $maxStocksAmount = min($totalBudget, $totalBudget * $riskLimit);
        $n = count($stocks);
        
        // Инициализация DP таблицы
        $dp = array_fill(0, $n + 1, array_fill(0, (int)$maxStocksAmount + 1, 0));
        $stocksAmount = array_fill(0, $n + 1, array_fill(0, (int)$maxStocksAmount + 1, 0));
        
        for ($i = 1; $i <= $n; $i++) {
            $cost = $stocks[$i-1][0];
            $profit = $stocks[$i-1][1];
            
            for ($j = 0; $j <= $maxStocksAmount; $j++) {
                // Не берем текущую акцию
                $dp[$i][$j] = $dp[$i-1][$j];
                $stocksAmount[$i][$j] = $stocksAmount[$i-1][$j];
                
                // Берем текущую акцию, если возможно
                if ($j >= $cost) {
                    $newProfit = $dp[$i-1][$j - $cost] + $profit;
                    if ($newProfit > $dp[$i][$j]) {
                        $dp[$i][$j] = $newProfit;
                        $stocksAmount[$i][$j] = $stocksAmount[$i-1][$j - $cost] + $cost;
                    }
                }
            }
        }
        
        // Находим оптимальное распределение
        $maxProfit = 0;
        $optimalStocksAmount = 0;
        
        for ($j = 0; $j <= $maxStocksAmount; $j++) {
            $stocksProfit = $dp[$n][$j];
            $bondsAmount = $totalBudget - $j;
            $bondsProfit = $bondsAmount * $bondYield;
            $totalProfit = $stocksProfit + $bondsProfit;
            
            if ($totalProfit > $maxProfit) {
                $maxProfit = $totalProfit;
                $optimalStocksAmount = $j;
            }
        }
        
        $optimalBondsAmount = $totalBudget - $optimalStocksAmount;
        
        return [$maxProfit, $optimalStocksAmount, $optimalBondsAmount];
    }
    
    /**
     * Трехмерное ДП с учетом временного горизонта
     * 
     * @param array $stocks Массив акций с ожидаемой доходностью за каждый год
     * @param float $bondYield Доходность облигаций
     * @param float $totalBudget Общий бюджет
     * @param float $riskLimit Лимит риска
     * @param int $years Временной горизонт
     * @return array [итоговая прибыль, распределение по годам]
     */
    public function max_profit_3d(array $stocks, float $bondYield, float $totalBudget, float $riskLimit, int $years): array
    {
        $maxStocksAmount = min($totalBudget, $totalBudget * $riskLimit);
        $n = count($stocks);
        
        // DP: [год][инвестиции в акции][акция]
        $dp = array_fill(0, $years + 1, 
                array_fill(0, (int)$maxStocksAmount + 1, 
                    array_fill(0, $n + 1, 0)));
        
        $distribution = [];
        
        for ($year = 1; $year <= $years; $year++) {
            for ($j = 0; $j <= $maxStocksAmount; $j++) {
                for ($i = 1; $i <= $n; $i++) {
                    $cost = $stocks[$i-1][0];
                    $annualProfit = $stocks[$i-1][1] / $years; // Упрощенное предположение
                    
                    // Не берем акцию
                    $dp[$year][$j][$i] = $dp[$year][$j][$i-1];
                    
                    // Берем акцию
                    if ($j >= $cost) {
                        $newProfit = $dp[$year-1][$j - $cost][$i-1] + $annualProfit;
                        if ($newProfit > $dp[$year][$j][$i]) {
                            $dp[$year][$j][$i] = $newProfit;
                        }
                    }
                }
            }
        }
        
        // Добавляем доходность от облигаций
        $maxProfit = 0;
        $optimalStocks = 0;
        
        for ($j = 0; $j <= $maxStocksAmount; $j++) {
            $stocksProfit = $dp[$years][$j][$n];
            $bondsAmount = $totalBudget - $j;
            $bondsProfit = $bondsAmount * $bondYield * $years;
            $totalProfit = $stocksProfit + $bondsProfit;
            
            if ($totalProfit > $maxProfit) {
                $maxProfit = $totalProfit;
                $optimalStocks = $j;
            }
        }
        
        return [$maxProfit, $optimalStocks, $totalBudget - $optimalStocks];
    }
    
    /**
     * Жадный алгоритм для сравнения (выбор по максимальной доходности)
     */
    public function greedy_approach(array $stocks, float $budget): array
    {
        // Сортируем по убыванию доходности (прибыль/стоимость)
        usort($stocks, function($a, $b) {
            $yieldA = $a[1] / $a[0];
            $yieldB = $b[1] / $b[0];
            return $yieldB <=> $yieldA;
        });
        
        $remainingBudget = $budget;
        $totalProfit = 0;
        $selected = [];
        
        foreach ($stocks as $index => $stock) {
            if ($stock[0] <= $remainingBudget) {
                $selected[] = $index;
                $remainingBudget -= $stock[0];
                $totalProfit += $stock[1];
            }
        }
        
        return [$totalProfit, $selected];
    }
}

/**
 * Класс для визуализации результатов
 */
class InvestmentVisualizer
{
    /**
     * Строит график зависимости прибыли от бюджета
     */
    public static function plotProfitVsBudget(array $stocks, float $maxBudget, int $steps = 20): void
    {
        $optimizer = new InvestmentOptimizer();
        $budgets = [];
        $profits = [];
        
        echo "График зависимости прибыли от бюджета:\n";
        echo "Бюджет\tПрибыль\n";
        echo str_repeat("-", 30) . "\n";
        
        for ($i = 1; $i <= $steps; $i++) {
            $budget = ($maxBudget / $steps) * $i;
            list($profit, ) = $optimizer->max_profit_1d($stocks, $budget);
            
            $budgets[] = $budget;
            $profits[] = $profit;
            
            echo sprintf("%6.0f\t%7.1f\n", $budget, $profit);
        }
        
        // Текстовая визуализация
        echo "\nТекстовая диаграмма:\n";
        $maxProfit = max($profits);
        
        foreach ($profits as $index => $profit) {
            $barLength = (int)($profit / $maxProfit * 50);
            echo sprintf("Бюджет %5.0f: %s (%.1f)\n", 
                $budgets[$index], 
                str_repeat("█", $barLength),
                $profit
            );
        }
    }
    
    /**
     * Сравнивает алгоритмы ДП и жадный
     */
    public static function compareAlgorithms(array $stocks, float $budget): void
    {
        $optimizer = new InvestmentOptimizer();
        
        echo "\nСравнение алгоритмов:\n";
        echo str_repeat("=", 50) . "\n";
        
        // Динамическое программирование
        $startTime = microtime(true);
        list($dpProfit, $dpSelected) = $optimizer->max_profit_1d($stocks, $budget);
        $dpTime = microtime(true) - $startTime;
        
        // Жадный алгоритм
        $startTime = microtime(true);
        list($greedyProfit, $greedySelected) = $optimizer->greedy_approach($stocks, $budget);
        $greedyTime = microtime(true) - $startTime;
        
        echo "Динамическое программирование:\n";
        echo "  Прибыль: " . $dpProfit . "\n";
        echo "  Выбрано акций: " . count($dpSelected) . "\n";
        echo "  Время выполнения: " . number_format($dpTime * 1000, 3) . " мс\n";
        
        echo "\nЖадный алгоритм:\n";
        echo "  Прибыль: " . $greedyProfit . "\n";
        echo "  Выбрано акций: " . count($greedySelected) . "\n";
        echo "  Время выполнения: " . number_format($greedyTime * 1000, 3) . " мс\n";
        
        echo "\nРазница: " . ($dpProfit - $greedyProfit) . " (" . 
            number_format(($dpProfit / $greedyProfit - 1) * 100, 2) . "%)\n";
    }
}

/**
 * Основной скрипт с примером использования
 */
function main()
{
    echo "СИСТЕМА ФОРМИРОВАНИЯ ИНВЕСТИЦИОННОГО ПОРТФЕЛЯ\n";
    echo str_repeat("=", 60) . "\n\n";
    
    // Пример данных
    $stocks = [
        [100, 10],  // [стоимость, прибыль]
        [200, 30],
        [150, 20],
        [80, 15],
        [120, 25]
    ];
    
    $bondsYield = 0.05; // 5%
    $totalBudget = 300;
    $riskLimit = 0.5; // 50%
    
    $optimizer = new InvestmentOptimizer();
    
    // 1. Одномерное ДП
    echo "1. ОДНОМЕРНОЕ ДИНАМИЧЕСКОЕ ПРОГРАММИРОВАНИЕ\n";
    echo str_repeat("-", 50) . "\n";
    
    list($profit1d, $selected1d) = $optimizer->max_profit_1d($stocks, $totalBudget);
    
    echo "Максимальная прибыль: " . $profit1d . "\n";
    echo "Выбранные акции (индексы): " . implode(", ", $selected1d) . "\n";
    echo "Состав портфеля:\n";
    
    $totalCost = 0;
    foreach ($selected1d as $index) {
        $cost = $stocks[$index][0];
        $profit = $stocks[$index][1];
        $totalCost += $cost;
        echo "  Акция #" . ($index + 1) . ": стоимость {$cost}, прибыль {$profit}\n";
    }
    echo "Использовано средств: {$totalCost} из {$totalBudget}\n";
    
    // 2. Двумерное ДП
    echo "\n2. ДВУМЕРНОЕ ДИНАМИЧЕСКОЕ ПРОГРАММИРОВАНИЕ\n";
    echo str_repeat("-", 50) . "\n";
    
    list($profit2d, $stocksAmount, $bondsAmount) = $optimizer->max_profit_2d(
        $stocks, $bondsYield, $totalBudget, $riskLimit
    );
    
    echo "Максимальная прибыль: " . number_format($profit2d, 2) . "\n";
    echo "Распределение:\n";
    echo "  Акции: " . number_format($stocksAmount, 2) . " (" . 
        number_format($stocksAmount / $totalBudget * 100, 1) . "%)\n";
    echo "  Облигации: " . number_format($bondsAmount, 2) . " (" . 
        number_format($bondsAmount / $totalBudget * 100, 1) . "%)\n";
    
    // 3. Трехмерное ДП (с временным горизонтом)
    echo "\n3. ТРЕХМЕРНОЕ ДП С ВРЕМЕННЫМ ГОРИЗОНТОМ (3 года)\n";
    echo str_repeat("-", 50) . "\n";
    
    list($profit3d, $stocks3d, $bonds3d) = $optimizer->max_profit_3d(
        $stocks, $bondsYield, $totalBudget, $riskLimit, 3
    );
    
    echo "Итоговая прибыль за 3 года: " . number_format($profit3d, 2) . "\n";
    echo "Распределение:\n";
    echo "  Акции: " . number_format($stocks3d, 2) . "\n";
    echo "  Облигации: " . number_format($bonds3d, 2) . "\n";
    
    // 4. Визуализация
    echo "\n4. ВИЗУАЛИЗАЦИЯ\n";
    echo str_repeat("-", 50) . "\n";
    
    InvestmentVisualizer::plotProfitVsBudget($stocks, $totalBudget * 1.5, 10);
    
    // 5. Сравнение алгоритмов
    echo "\n5. ТЕСТИРОВАНИЕ И СРАВНЕНИЕ\n";
    echo str_repeat("-", 50) . "\n";
    
    InvestmentVisualizer::compareAlgorithms($stocks, $totalBudget);
    
    // 6. Тестирование на различных наборах данных
    echo "\n6. ТЕСТИРОВАНИЕ НА РАЗЛИЧНЫХ НАБОРАХ\n";
    echo str_repeat("-", 50) . "\n";
    
    $testCases = [
        'Маленький набор' => [[50, 10], [100, 30], [150, 40]],
        'Большой набор' => [
            [50, 5], [75, 10], [100, 15], [120, 20],
            [150, 25], [200, 35], [250, 45]
        ],
        'Высокая доходность' => [[100, 50], [200, 90], [300, 120]]
    ];
    
    foreach ($testCases as $name => $testStocks) {
        echo "\n{$name}:\n";
        list($dpProfit, ) = $optimizer->max_profit_1d($testStocks, 300);
        list($greedyProfit, ) = $optimizer->greedy_approach($testStocks, 300);
        
        echo "  ДП: {$dpProfit}, Жадный: {$greedyProfit}, ";
        echo "Разница: " . ($dpProfit - $greedyProfit) . "\n";
    }
}

// Запуск основной программы
main();

/**
 * Дополнительные тесты и примеры
 */
echo "\n\nДОПОЛНИТЕЛЬНЫЕ ПРИМЕРЫ:\n";
echo str_repeat("=", 60) . "\n";

// Пример из условия задачи
echo "\nПример из условия задачи:\n";
$exampleStocks = [[100, 10], [200, 30], [150, 20]];
$optimizer = new InvestmentOptimizer();

// Одномерное ДП
list($profit, $selected) = $optimizer->max_profit_1d($exampleStocks, 300);
echo "Одномерное ДП: прибыль {$profit} (акции " . 
    implode(" и ", array_map(function($x) { return $x + 1; }, $selected)) . ")\n";

// Двумерное ДП
list($profit2d, $stocksAmount, $bondsAmount) = $optimizer->max_profit_2d(
    $exampleStocks, 0.05, 300, 0.5
);
echo "Двумерное ДП: акции: {$stocksAmount}, облигации: {$bondsAmount}, прибыль: " . 
    number_format($profit2d, 1) . "\n";
