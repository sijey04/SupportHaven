import tkinter as tk
from tkinter import ttk

class FinanceTracker:
    def __init__(self, master):
        self.master = master
        master.title("Personal Finance Tracker")
        master.geometry("400x300")

        self.income_label = ttk.Label(master, text="Income:")
        self.income_label.grid(row=0, column=0, padx=5, pady=5, sticky="e")
        self.income_entry = ttk.Entry(master)
        self.income_entry.grid(row=0, column=1, padx=5, pady=5)

        self.expense_label = ttk.Label(master, text="Expense:")
        self.expense_label.grid(row=1, column=0, padx=5, pady=5, sticky="e")
        self.expense_entry = ttk.Entry(master)
        self.expense_entry.grid(row=1, column=1, padx=5, pady=5)

        self.add_button = ttk.Button(master, text="Calculate", command=self.add_entry)
        self.add_button.grid(row=2, column=0, columnspan=2, pady=10)

        self.result_text = tk.Text(master, height=8, width=40)
        self.result_text.grid(row=3, column=0, columnspan=2, padx=5, pady=5)

        self.total_income = 0
        self.total_expenses = 0

    def add_entry(self):
        income = float(self.income_entry.get() or 0)
        expense = float(self.expense_entry.get() or 0)

        self.total_income += income
        self.total_expenses += expense

        self.update_display()
        self.clear_entries()

    def update_display(self):
        balance = self.total_income - self.total_expenses
        display_text = f"Total Income: {self.total_income:.2f} pesos\n"
        display_text += f"Total Expenses: {self.total_expenses:.2f} pesos\n"
        display_text += f"Remaining Balance: {balance:.2f} pesos"

        self.result_text.delete(1.0, tk.END)
        self.result_text.insert(tk.END, display_text)

    def clear_entries(self):
        self.income_entry.delete(0, tk.END)
        self.expense_entry.delete(0, tk.END)

if __name__ == "__main__":
    root = tk.Tk()
    app = FinanceTracker(root)
    root.mainloop()
