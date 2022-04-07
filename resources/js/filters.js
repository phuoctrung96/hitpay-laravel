import moment from 'moment'

export default (Vue) => {
  Vue.filter("dateTime", val => !val? val: moment(String(val)).format('MMM. DD, YYYY, h:mm a'));
  Vue.filter("thousands", val => val.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,'))
  Vue.filter("currency", val => Number(val).toFixed(2).toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,'))
  Vue.filter("date", val => !val? val: moment(String(val)).format('MMM. DD, YYYY'))

  Vue.filter("date2", val => {
    if (moment(val).isSame(moment(), "day")) {
      // today
      return moment(val).format('hh:mm A') + ' Today'
    } else if (moment(val).isSame(moment().subtract(1, 'day'), "day")) {
      // yesterday
      return moment(val).format('hh:mm A') + ' Yesterday'
    } else {
      return moment(String(val)).format('DD MMM')
    }
  })

  Vue.filter("round", val => Number(val).toFixed(2))

  Vue.filter('capitalize', val => { if (!val) return ''
    val = val.toString()
    return val.charAt(0).toUpperCase() + val.slice(1)
  })

  Vue.filter('optimizeText', val => { if (!val) return ''
    return val.replace(/[-_]/g, ' ')
  })
}

export function mapFilters(filters) {
  return filters.reduce((result, filter) => {
    result[filter] = function(...args) {
      return this.$options.filters[filter](...args)
    }
    return result
  }, {})
}
