export default {
    methods: {
        formatAmount(amount) {
            return Number(amount).toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,')
        }
    }
}