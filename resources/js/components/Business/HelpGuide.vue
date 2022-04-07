<template>
    <div class="help-guides mt-4 mb-4" v-if="help_options.length > 0">
        <div class="card shadow-sm">
            <div class="card-body  p-4">
                <div class="top-title border-bottom">
                    <h3>Help options</h3>
                </div>
                <div class="all-items">
                    <div v-for="(option, index) in help_options" class="item" :key="index">
                        <div class="link-item clearfix" :class="(option.type === 'video') ? 'video' : ((option.type === 'guide') ? 'guide' : 'link')">
                            <div class="icon">
                                <span>
                                    <img src="/images/ico-video.svg" alt="" v-if="option.type === 'video'">
                                    <img src="/images/ico-guide.svg" alt="" v-if="option.type === 'guide'">
                                    <img src="/images/ico-link.svg" alt="" v-if="option.type === 'internal link'">
                                </span>
                            </div>
                            <div class="information">
                                <div class="title">
                                    <p>{{option.title}}</p>
                                </div>
                                <div class="sub-title">
                                    <p>{{option.subtitle}}</p>
                                </div>
                                <div class="is-link">
                                    <a href="" data-target="#modalYoutube" data-toggle="modal"  v-if="option.type === 'video'" @click="sendPostHog">
                                        <div class="icon-play">
                                            <span>
                                                <img src="/images/ico-play-video.svg" alt="">
                                            </span>
                                        </div>
                                        <span>Watch how it works</span>
                                    </a>
                                    <p v-if="option.type == 'guide'">
                                        <template v-for="(link, index) in option.link" class="item">
                                            <a :href="link" target="_blank" @click="sendPostHog" :key="index">{{link}}</a>
                                        </template>
                                    </p>
                                    <p v-if="option.type == 'internal link'">
                                        <template v-for="(link, index) in option.link" class="item">
                                            <a :href="link" @click="sendPostHog" :key="index">{{link}}</a>
                                        </template>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!--Modal Youtube-->
                        <div class="modal fade modal-youtube-iframe" id="modalYoutube" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" v-if="option.type === 'video'">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">        
                                        <div class="embed-responsive embed-responsive-16by9">
                                            <div v-for="(link, index) in option.link" class="item" :key="index">
                                                <iframe class="embed-responsive-item" :src="getUrlEmbed(link)" id="video" frameborder="0"  allowscriptaccess="always" allow="autoplay"></iframe>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
    export default {
        name: "HelpGuide",
        props: {
            page_type: {
                type: String,
            },
        },
        data() {
            return {
                help_options: []
            }
        },
        created() {
            this.getHelpGuide();
        },
        mounted() {
            
        },
        methods: {
            getHelpGuide(){
                axios.post(this.getDomain('help-guide', 'dashboard'), {
                    page_type: this.page_type,
                }).then(({data}) => {
                    if(data != null)
                        this.help_options = JSON.parse(data.help_options);
                        console.log(this.help_options);
                }).catch(({response}) => {
                     console.log(response);
                });
            },
            getUrlEmbed($url) {
                let res = $url.split("=");
                let embeddedUrl = "https://www.youtube.com/embed/"+res[1];
                embeddedUrl += "?autoplay=1&amp;modestbranding=1&amp;showinfo=0&amp;mute=1"
                return embeddedUrl;
            },
            sendPostHog() {
                this.postHogOnlyCaptureData('help_guide_clicked', '');
            }
        }
    }
</script>

<style lang="scss">
    .modal-youtube-iframe{
        .modal-dialog {
            max-width: 800px;
            .modal-body {
                padding:0px;
            }
        }
    }
</style>