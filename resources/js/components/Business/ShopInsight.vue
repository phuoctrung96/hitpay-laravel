<template>
    <div class="page-insights">
        <div class="widget-insight">
            <div class="steps">
                <div class="top-title d-flex justify-content-between align-items-center">
                     <h5>Insights</h5>
                </div>
                <div class="top-product">
                    <div class="row">
                        <div class="col-12 col-lg-6">
                            <TopProducts
                                v-if="loaded_product"
                                :business_id="business_id"
                                :data_label="data_product_label"
                                :data_percent="data_product_percent"
                                :data_total="data_product_value"
                                :height="height"/>
                            <div class="widget-chart hl" v-if="show_message_product">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <div class="top-title d-flex justify-content-between align-items-center">
                                            <h5 class="title">Top products</h5>
                                        </div>
                                        <div class="text">
                                            There were no sales in this date range.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <TotalOrders
                                v-if="loaded_order"
                                :business_id="business_id"
                                :data_order="data_order"
                                :data_order_label="data_order_label"
                                :data_max="data_order_max"/>
                            <div class="widget-chart hl" v-if="show_message_order">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <div class="top-title d-flex justify-content-between align-items-center">
                                            <h5 class="title">Top products</h5>
                                        </div>
                                        <div class="text">
                                            There were no orders in this date range.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>    
                    </div>
                </div>
                <a href="#" @click="TriggerInsightsSurvey()" style="float: right;">How can the Insights be improved? </a>
            </div>
        </div>
        <div class="smd-next-steps">
            <div class="steps">
                 <div class="top-title d-flex justify-content-between align-items-center">
                     <h5>Next steps</h5>
                 </div>
                <div class="row">
                    <div class="col-12 col-sm-6 col-md-6 col-lg-3">
                        <div class="item product-categories h-100">
                            <div class="card shadow-sm mb-4 h-100">
                                <div class="thumbnail">
                                    <img src="/images/ico-next-step-01.png"/>
                                </div>
                                <div class="flex-grow-1 pd-information">
                                    <h6>Product categories</h6>
                                    <div class="text">
                                        <p>Organise your products and help your customers find what they're looking for.</p>
                                    </div>
                                </div>
                                <button class="btn btn-primary d-block mgb" @click="redirectCategory()">Add product categories</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-6 col-lg-3">
                        <div class="item coupons h-100">
                            <div class="card shadow-sm mb-4 h-100">
                                <div class="thumbnail">
                                    <img src="/images/ico-next-step-02.png"/>
                                </div>
                                <div class="flex-grow-1 pd-information">
                                    <div class="flex-grow-1">
                                        <h6>Coupons</h6>
                                        <div class="text">
                                            <p>Customers will get a discount if they apply coupon on checkout page.</p>
                                        </div>
                                    </div>
                                </div>
                                <button class="btn btn-primary d-block mgb" @click="redirectCoupons()">Add coupons</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-6 col-lg-3">
                        <div class="item discount hl h-100">
                            <div class="card shadow-sm mb-4 h-100">
                                <div class="thumbnail">
                                    <img src="/images/ico-next-step-05.png"/>
                                </div>
                                <div class="flex-grow-1 pd-information">
                                    <div class="flex-grow-1">
                                        <h6>Discount</h6>
                                        <div class="text">
                                            <p>Customers will get a discount automatically in their cart.</p>
                                        </div>
                                    </div>                                    
                                </div>
                                <button class="btn btn-primary d-block mgb" @click="redirectDiscount()">Add discount</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-6 col-lg-3">
                        <div class="item store-settings h-100">
                            <div class="card shadow-sm mb-4 h-100">
                                <div class="thumbnail">
                                    <img src="/images/ico-next-step-03.png"/>
                                </div>
                                <div class="flex-grow-1 pd-information">
                                    <div class="flex-grow-1">
                                        <h6>Store settings</h6>
                                        <div class="text">
                                            <p>Upload a cover image, add about us section and custom thank you message.</p>
                                        </div>
                                    </div>
                                </div>
                                <button class="btn btn-primary d-block mgb" @click="redirectStoreSetting()">Add settings</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import TotalOrders from "./TotalOrders";
import TopProducts from "./TopProducts";
export default {
    name: 'ShopInsight',
    components: {
        TotalOrders,
        TopProducts
    },
    props: {
        business_id: String,
        refiner_insights_survey_key: String,
    },
    watch: {
    },
    data() {
        return {
            loaded_order: false,
            loaded_product: false,
            show_message_order: false,
            show_message_product: false,
            data_order: [],
            data_order_label: [],
            data_product_value: [],
            data_product_label: [],
            data_product_percent: [],
            data_order_max: 0,
            height: 0,
        }
    },
    mounted() {
        this.getInsight();
        this.postHogOnlyCaptureData('View Insights', '');
    },
    methods: {
        TriggerInsightsSurvey() {
            _refiner('showForm', this.refiner_insights_survey_key);
        },
        redirectCategory() {
            window.location.href = this.getDomain(`business/${ this.business_id }/product-categories`, "dashboard");
        },
        redirectCoupons() {
            window.location.href = this.getDomain(`business/${ this.business_id }/coupon`, "dashboard");
        },
        redirectStoreSetting() {
            window.location.href = this.getDomain(`business/${ this.business_id }/setting/shop`, "dashboard");
        },
        redirectDiscount() {
            window.location.href = this.getDomain(`business/${ this.business_id }/discount`, "dashboard");
        },
        async getInsight() {
            this.loaded_order = false;
            this.loaded_product = false;
            this.show_message_order = false;
            this.show_message_product = false;

            await axios.get(this.getDomain(`v1/business/${this.business_id}/shop/insights/?period=current_week`, 'api'), {
                withCredentials: true
            }).then(response => {
                let orders = response.data.total_orders;
                let totalProducts = response.data.top_products;
                
                orders.forEach(item=> {
                    this.data_order.push(item.count);
                    this.data_order_label.push(this.getDayOfWeek(item.date));

                    if(parseInt(item.count) > this.data_order_max) {
                        this.data_order_max = parseInt(item.count)
                    }    
                });

                this.addDayOfWeek();
                
                const data = [];
                var total = 0;
                _.each( totalProducts, function( val, key ) {
                    data.push(val);
                    total += val.count;
                });
                
                let count = 0;
                data.forEach(item => {
                    this.data_product_label.push(item.name);
                    this.data_product_value.push(item.count);

                    count ++
                    if(item.percent){
                        this.data_product_percent.push(item.percent + "%");
                    }else{
                        let percent = ((item.count / total) * 100).toFixed(2);
                        this.data_product_percent.push(percent + "%");
                    }
                });

                this.height = count * 45

                if(totalProducts){
                    this.loaded_product = true;
                }else{
                    this.show_message_product = true;
                }

                if(orders.length > 0) {
                    this.loaded_order = true;
                }else {
                    this.show_message_order = true;
                }
            });
        }, 
        getDayOfWeek(date){
            const d = new Date(date);
            let day = d.getDay();
            switch(day){
                case 0:
                    return "Sun";
                case 1:
                    return "Mon";
                case 2:
                    return "Tue";
                case 3:
                    return "Wed";
                case 4:
                    return "Thu";
                case 5:
                    return "Fri";
                case 6:
                    return "Sat";                
            }
        },
        addDayOfWeek() {
            if(!this.data_order_label.includes('Mon')) {
                this.data_order_label.splice(0,0,"Mon");
                this.data_order.splice(0,0,0);
            }

            if(!this.data_order_label.includes('Tue')) {
                this.data_order_label.splice(1,0,"Tue");
                this.data_order.splice(1,0,0);
            }

            if(!this.data_order_label.includes('Wed')) {
                this.data_order_label.splice(2,0,"Wed");
                this.data_order.splice(2,0,0);
            }

            if(!this.data_order_label.includes('Thu')) {
                this.data_order_label.splice(3,0,"Thu");
                this.data_order.splice(3,0,0);
            }

            if(!this.data_order_label.includes('Fri')) {
                this.data_order_label.splice(4,0,"Fri");
                this.data_order.splice(4,0,0);
            }

            if(!this.data_order_label.includes('Sat')) {
                this.data_order_label.splice(5,0,"Sat");
                this.data_order.splice(5,0,0);
            }

            if(!this.data_order_label.includes('Sun')) {
                this.data_order_label.splice(6,0,"Sun");
                this.data_order.splice(6,0,0);
            }
        }
    }
}
</script>
