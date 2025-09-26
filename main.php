from typing import List, Tuple

class InvestmentOptimizer:
    def __init__(self):
        pass
    
    def max_profit_1d(self, stocks: List[Tuple[int, int]], budget: int) -> Tuple[int, List[Tuple[int, int]]]:
        """
        Одномерное ДП (задача о рюкзаке) для максимизации прибыли от акций
        """
        n = len(stocks)
        # Инициализация DP таблицы
        dp = [[0] * (budget + 1) for _ in range(n + 1)]
        
        # Заполнение DP таблицы
        for i in range(1, n + 1):
            cost, profit = stocks[i-1]
            for j in range(1, budget + 1):
                if cost <= j:
                    dp[i][j] = max(dp[i-1][j], dp[i-1][j-cost] + profit)
                else:
                    dp[i][j] = dp[i-1][j]
        
        # Восстановление выбранных акций
        selected_stocks = []
        j = budget
        for i in range(n, 0, -1):
            if dp[i][j] != dp[i-1][j]:
                cost, profit = stocks[i-1]
                selected_stocks.append((cost, profit))
                j -= cost
        
        return dp[n][budget], selected_stocks
    
    def max_profit_2d(self, stocks: List[Tuple[int, int]], bonds_yield: float, 
                      total_budget: int, risk_limit: int) -> Tuple[float, int, int]:
        """
        Двумерное ДП для оптимизации распределения между акциями и облигациями
        """
        max_stock_investment = min(risk_limit, total_budget)
        
        # Создаем массив для максимальной прибыли от акций при разных инвестициях
        stock_profits = [0] * (max_stock_investment + 1)
        
        # Заполняем массив прибылей от акций
        for investment in range(1, max_stock_investment + 1):
            profit, _ = self.max_profit_1d(stocks, investment)
            stock_profits[investment] = profit
        
        # Оптимизируем распределение средств
        max_total_profit = 0
        optimal_stocks = 0
        optimal_bonds = 0
        
        for stock_investment in range(max_stock_investment + 1):
            bond_investment = total_budget - stock_investment
            if bond_investment < 0:
                continue
                
            stock_profit = stock_profits[stock_investment]
            bond_profit = bond_investment * bonds_yield / 100
            total_profit = stock_profit + bond_profit
            
            if total_profit > max_total_profit:
                max_total_profit = total_profit
                optimal_stocks = stock_investment
                optimal_bonds = bond_investment
        
        return max_total_profit, optimal_stocks, optimal_bonds
    
    def print_profit_table(self, stocks: List[Tuple[int, int]], max_budget: int):
        """
        Текстовая таблица зависимости прибыли от бюджета
        """
        print("Бюджет | Прибыль | Выбранные акции")
        print("-" * 50)
        
        for budget in range(10, max_budget + 1, 10):  # Шаг 10 для наглядности
            profit, selected = self.max_profit_1d(stocks, budget)
            selected_str = ", ".join([f"({c},{p})" for c, p in selected])
            print(f"{budget:6} | {profit:7} | {selected_str}")
    
    def compare_with_greedy(self, stocks: List[Tuple[int, int]], budget: int):
        """
        Сравнение с жадным алгоритмом (по эффективности прибыли на стоимость)
        """
        # Жадный алгоритм: сортируем по убыванию прибыли на стоимость
        sorted_stocks = sorted(stocks, key=lambda x: x[1]/x[0], reverse=True)
        
        current_budget = budget
        greedy_profit = 0
        greedy_selected = []
        
        for cost, profit in sorted_stocks:
            if cost <= current_budget:
                greedy_selected.append((cost, profit))
                greedy_profit += profit
                current_budget -= cost
        
        # Решение методом ДП
        dp_profit, dp_selected = self.max_profit_1d(stocks, budget)
        
        print("\n=== Сравнение с жадным алгоритмом ===")
        print(f"ДП: прибыль = {dp_profit}, акции = {dp_selected}")
        print(f"Жадный: прибыль = {greedy_profit}, акции = {greedy_selected}")
        print(f"Разница: {dp_profit - greedy_profit}")

# Пример использования
if __name__ == "__main__":
    optimizer = InvestmentOptimizer()
    
    # Тестовые данные
    stocks = [(100, 10), (200, 30), (150, 20)]
    bonds_yield = 5  # 5%
    budget = 300
    risk_limit = 150  # Не более 50% в акции
    
    # Одномерное ДП
    profit_1d, selected_stocks = optimizer.max_profit_1d(stocks, budget)
    print("=== Одномерное ДП (задача о рюкзаке) ===")
    print(f"Максимальная прибыль: {profit_1d}")
    print(f"Выбранные акции: {selected_stocks}")
    
    # Двумерное ДП
    profit_2d, stocks_amount, bonds_amount = optimizer.max_profit_2d(
        stocks, bonds_yield, budget, risk_limit
    )
    print("\n=== Двумерное ДП (оптимизация риска) ===")
    print(f"Общая прибыль: {profit_2d}")
    print(f"Инвестиции в акции: {stocks_amount}")
    print(f"Инвестиции в облигации: {bonds_amount}")
    
    # Визуализация в виде таблицы
    print("\n=== Зависимость прибыли от бюджета ===")
    optimizer.print_profit_table(stocks, 400)
    
    # Сравнение с жадным алгоритмом
    optimizer.compare_with_greedy(stocks, budget)
    
    # Дополнительный пример с большим набором акций
    print("\n" + "="*50)
    print("ДОПОЛНИТЕЛЬНЫЙ ПРИМЕР")
    print("="*50)
    
    more_stocks = [(100, 15), (200, 35), (150, 25), (50, 5), (300, 50)]
    budget2 = 500
    risk_limit2 = 300
    
    profit_1d2, selected2 = optimizer.max_profit_1d(more_stocks, budget2)
    print(f"Одномерное ДП: прибыль = {profit_1d2}, акции = {selected2}")
    
    profit_2d2, stocks2, bonds2 = optimizer.max_profit_2d(
        more_stocks, bonds_yield, budget2, risk_limit2
    )
    print(f"Двумерное ДП: прибыль = {profit_2d2:.2f}, акции = {stocks2}, облигации = {bonds2}")
