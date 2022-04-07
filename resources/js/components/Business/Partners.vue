<style scoped>
.tool-tip {
    font-size: 12px;
    position: relative;
    display: inline-block;
    border-bottom: 1px dotted black; /* If you want dots under the hoverable text */
}

/* Tooltip text */
.tool-tip .tooltip-text {
    visibility: hidden;
    width: 150px;
    background-color: black;
    color: #fff;
    text-align: center;
    padding: 5px 0;
    border-radius: 6px;

    /* Position the tooltip text - see examples below! */
    position: absolute;
    z-index: 1;
}

/* Show the tooltip text when you mouse over the tooltip container */
.tool-tip:hover .tooltip-text {
    visibility: visible;
}

.main-dashboard .dashboard-ql {
    height: 170px !important;
}
</style>
<template>
    <div>
        <div class="card border-0 shadow-sm mb-3 mx-3">
            <div class="card-body">
                <template v-for="category in categories">
                    <div class="form-check ml-3 d-inline-block">
                        <input class="form-check-input" type="checkbox" :value="category" v-model="selectedCategories"
                               :id="'category-'+category"><label
                        :for="'category-'+category">{{ category }}</label>
                    </div>
                </template>
            </div>
        </div>
        <div class="d-flex flex-wrap">
            <div v-for="partner in activePartners" class="dashboard-ql m-3 partner">
                <a :href="partner.link" target="_blank" class="text-center h-100 d-block">
                    <div class="h-100 d-flex flex-column align-items-center justify-content-between">
                        <div class="flex-grow-1 d-flex align-items-center">
                            <img :src="partner.logo" alt="" width="90">
                        </div>
                        <span class="ql-name text-dark">{{ partner.name }}</span>
                        <div v-if="partner.offer" class="tool-tip">Partner offer
                            <span class="tooltip-text">{{ partner.offer }}</span>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "Partners",
    data() {
        return {
            partners:
                [
                    {
                        name: 'Web Delegate',
                        categories: ['E-Commerce Website Design and Development', 'Digital Marketing'],
                        link: 'http://www.webdelegate.com',
                        logo: '/images/partners-logo/web-delegate-logo.png',
                    },
                    {
                        name: 'SMMILE',
                        categories: ['E-Commerce Website Design and Development', 'Digital Marketing'],
                        link: 'https://smmile.com',
                        logo: '/images/partners-logo/smile-logo.png'
                    },
                    {
                        name: 'Pixel Mechanics Pte Ltd',
                        categories: ['E-Commerce Website Design and Development', 'Digital Marketing'],
                        link: 'https://pixelmechanics.com.sg/',
                        logo: '/images/partners-logo/pixel-mechanics.png'
                    },
                    {
                        name: '360&5',
                        categories: ['E-Commerce Website Design and Development', 'Digital Marketing'],
                        link: 'https://www.360and5.com/',
                        logo: '/images/partners-logo/360-logo.png'
                    },
                    {
                        name: 'Jumpstart Commerce',
                        categories: ['E-Commerce Website Design and Development', 'Digital Marketing'],
                        link: 'https://www.jumpstartcommerce.com',
                        logo: '/images/partners-logo/jumpstart-logo.png',
                    },
                    {
                        name: 'OSOME',
                        categories: ['Incorporation and Corporate Secretary Services', 'Accountancy and Bookkeeping',],
                        link: 'https://osome.com/sg/',
                        logo: '/images/partners-logo/osome-logo.jpg',
                        offer: '8% off accountancy and bookkeeping services when you quote HITPAY18',
                    },
                    {
                        name: 'Verz Design Pte Ltd',
                        categories: ['E-Commerce Website Design and Development', 'Digital Marketing'],
                        link: 'https://www.verzdesign.com/',
                        logo: '/images/partners-logo/verz-logo.png',
                        offer: 'Fixed 10% discount for all e-commerce website development solutions + $300 cash credits for SEO, SEM & SMM services (minimum campaign duration of 6 months)',
                    },
                    {
                        name: 'Corsiva Labs',
                        categories: ['E-Commerce Website Design and Development', 'Digital Marketing'],
                        link: 'https://www.corsivalab.com',
                        logo: '/images/partners-logo/corsiva-lab-logo.png'
                    },
                    {
                        name: 'eFusion Technology Pte Ltd',
                        categories: ['E-Commerce Website Design and Development', 'Digital Marketing'],
                        link: 'https://www.efusiontech.com',
                        logo: '/images/partners-logo/efusion-logo.png',
                        offer: '80% Productivity Solution Grant (PSG)',
                    },
                    {
                        name: 'SHIPPIT PTE LTD',
                        categories: ['Orders & Shipping'],
                        link: 'https://www.shippit.com/',
                        logo: '/images/partners-logo/shippit.png',
                    },
                    {
                        name: 'Brew Creative Pte Ltd',
                        categories: ['E-Commerce Website Design and Development', 'Digital Marketing'],
                        link: 'https://iheartbrew.com',
                        logo: '/images/partners-logo/brew-logo.png',
                        offer: '10% off on projects above $2500',
                    },
                    {
                        name: 'Solstium Pte Ltd',
                        categories: ['E-Commerce Website Design and Development', 'Digital Marketing'],
                        link: 'https://solstium.net/',
                        logo: '/images/partners-logo/solstium-logo.png',
                        offer: '10% OFF',
                    },
                    {
                        name: 'iFoundries (Asia Pacific) Pte Ltd',
                        categories: ['E-Commerce Website Design and Development', 'Digital Marketing'],
                        link: 'http://www.ifoundries.com',
                        logo: '/images/partners-logo/i-foundries-logo.png',
                        offer: '20% off all services for HitPay Merchants',
                    },
                    {
                        name: 'Biz Square Pte Ltd',
                        categories: ['Incorporation and Corporate Secretary Services', 'Accountancy and Bookkeeping'],
                        link: 'http://bizsquare.sg',
                        logo: '/images/partners-logo/biz-logo.png',
                    },
                    {
                        name: 'Count on Me Pte. Ltd',
                        categories: ['Incorporation and Corporate Secretary Services', 'Accountancy and Bookkeeping',],
                        link: 'http://countonme.biz',
                        logo: '/images/partners-logo/count-on-logo.png',
                        offer: '2 to 4 hours in person workshop/consultation on finance related topics. 10% off standard accounting fees if sign up for a year of outsourcing accounting.',
                    },
                    {
                        name: 'EasyStore',
                        categories: ['E-Commerce Website Design and Development'],
                        link: 'http://www.easystore.co',
                        logo: '/images/partners-logo/easystore-logo.png',
                    },
                    {
                        name: 'W.L.P PTE LTD',
                        categories: ['Incorporation and Corporate Secretary Services', 'Accountancy and Bookkeeping', 'Other Software Providers'],
                        link: 'https://www.wlp.com.sg',
                        logo: '/images/partners-logo/wlp-pte-logo.png',
                    },
                    {
                        name: 'REZZKEY SOLUTIONS PTE LTD',
                        categories: ['E-Commerce Website Design and Development','Digital Marketing'],
                        link: 'https://www.rezzkey.com',
                        logo: '/images/partners-logo/rezzkey-logo.png',
                    },
                    {
                        name: 'WLP DIGITAL',
                        categories: ['E-Commerce Website Design and Development'],
                        link: 'https://www.digitalsolutions.com.sg',
                        logo: '/images/partners-logo/wlp-digital-logo.jpg',
                        offer: '10% Discount for All E-Commerce Web Development Solutions + S$300 Cash Credits for SMM services (Min. Campaign Duration of 6 Months)'
                    },
                    {
                        name: 'OClass',
                        categories: ['Class Management Software'],
                        link: 'https://oclass.app',
                        logo: '/images/partners-logo/oclass-logo.png',
                    },
                ],
            categories: [
                'E-Commerce Website Design and Development',
                'Digital Marketing',
                'Co-Working Space',
                'Incorporation and Corporate Secretary Services',
                'Accountancy and Bookkeeping',
                'Photography',
                'Other Software Providers',
                'Class Management Software'
            ],
            selectedCategories: [],
        };
    },
    computed:{
        activePartners: function() {
            if(this.selectedCategories.length == 0) return this.partners;

            var activeCategories = [];
            var filters = this.selectedCategories;

            this.partners.forEach(function(partner) {

                function partnerContainsFilter(filter) {
                    return partner.categories.indexOf(filter) != -1;
                }

                if(filters.every(partnerContainsFilter)) {
                    activeCategories.push(partner);
                }
            });

            return activeCategories;
        }
    }
}
</script>
