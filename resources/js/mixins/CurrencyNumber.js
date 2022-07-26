import { param } from "jquery";

export default {
    methods: {
        getZeroDecimalCurrencies() {
            return [
                'bif',
                'clp',
                'djf',
                'gnf',
                'jpy',
                'kmf',
                'krw',
                'mga',
                'pyg',
                'rwf',
                'ugx',
                'vnd',
                'vuv',
                'xaf',
                'xof',
                'xpf'
            ];
        },
        currencyIsNormal(currency) {
            const zeroDecimalCurrencies = this.getZeroDecimalCurrencies();

            return !zeroDecimalCurrencies.includes(currency);
        },
        currencyIsZeroDecimal(currency) {
            const zeroDecimalCurrencies = this.getZeroDecimalCurrencies();

            return zeroDecimalCurrencies.includes(currency);
        },
        getRealAmountForCurrency(currency, amount) {
            if (this.currencyIsNormal(currency)) {
                return parseFloat(amount / 100);
            } else if(this.currencyIsZeroDecimal(currency)) {
                return amount;
            }

            return amount;
        },
        getFormattedAmount(paramCurrency, amount, withCurrencyCode = true) {
            if (typeof paramCurrency === 'undefined') {
                return amount;
            }

            const formattedAmount = this.getRealAmountForCurrency(paramCurrency, amount);

            if (withCurrencyCode) {
                return paramCurrency.toUpperCase() + formattedAmount;
            }

            return formattedAmount;
        }
    }
}
