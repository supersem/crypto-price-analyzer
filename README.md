# Cryptocurrency Price Analysis Service

This is a Laravel-based service for analyzing cryptocurrency prices across multiple exchanges. The service fetches the latest prices for selected trading pairs from the following exchanges:

- Binance
- JBEX
- Poloniex
- Bybit
- Whitebit

## Features

- Fetch real-time prices for common trading pairs across all supported exchanges.
- Display the lowest and highest price for a specific trading pair along with the exchange name.
- Calculate and display potential profit by comparing the lowest price on one exchange and the highest price on another.

## Prerequisites

- Docker
- Composer
- Laravel Sail

## Installation

Follow these steps to install and run the project locally:

### 1. Clone the repository

```bash
git clone https://github.com/supersem/crypto-price-analyzer.git
cd crypto-price-analyzer
```
### 2. Install dependencies
```bash
composer install
```
### 3. Set up environment variables
Copy .env.example to .env and configure your environment settings.

```bash
cp .env.example .env
```
Make sure to update any API keys and database credentials as needed in the .env file.

### 4. Install Laravel Sail
If Laravel Sail is not already installed, you can install it as a development dependency:

```bash
composer require laravel/sail --dev
```
### 5. Start Docker container with Sail
Run the following command to start the application in a Docker container:

```bash
./vendor/bin/sail up -d
```
### 6. Run migrations
Execute the database migrations:

```bash
./vendor/bin/sail artisan migrate
```
### 7. Running the Price Analysis Commands
You can run the price analysis manually or set it up as an automated process.

7.1. Manually Fetch Prices
To manually analyze the prices for a specific trading pair, use the following command:

```bash
./vendor/bin/sail artisan price:analyze BTC/USDT
```
This command will display the lowest and highest prices for the specified trading pair across all exchanges.

7.2. List Trading Pairs with Profit Calculation
To list trading pairs along with their potential profit, run:

```bash
./vendor/bin/sail artisan price:profit
```
This will display a list of pairs, the lowest price, the highest price, and the potential percentage profit between exchanges.

