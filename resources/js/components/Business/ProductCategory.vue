<template>
    <div>
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <h2 class="text-primary mb-0 title">Product Category</h2>
            </div>
            <div class="card-body border-top">
                <form id="business-discount" ref="businessDiscount">
                    <div class="form-group">
                        <label for="name">Name<span class="text-danger">*</span></label>
                        <input id="name" type="text" v-model="form.name" :class="{'is-invalid' : errors.name}" class="form-control bg-light" >
                        <span class="invalid-feedback" role="alert" v-if="errors.name">{{ errors.name }}</span>
                    </div>
                    <div class="form-group">
                        <label for="desc">Description</label>
                        <input id="desc" type="text" v-model="form.description" :class="{'is-invalid' : errors.description}" class="form-control bg-light" >
                        <span class="invalid-feedback" role="alert" v-if="errors.description">{{ errors.description }}</span>
                    </div>
                    <div class="form-group">

                        <div class="form-check">
                            <input class="form-check-input" v-model="form.active" type="radio" id="active" name="active" value="1" checked required>
                            <label class="form-check-label" for="active">
                                Active
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input"  v-model="form.active" required  type="radio" id="non-active" name="active" value="0">
                            <label class="form-check-label" for="non-active" >
                                Not active
                            </label>
                        </div>
                    </div>
                </form>
                <button id="createBtn" class="btn btn-success btn-lg btn-block mb-3 shadow-sm" @click="createCategory()" :disabled="is_busy">
                   {{'Save Changes'}}
                    <i class="fas fa-spin fa-spinner" :class="{
                        'd-none' : !is_busy
                    }"></i></button>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        name: "ProductCategory",
        data: () => {
            return {
                is_loading: false,
                is_busy: false,
                form: {
                    id: null,
                    name: '',
                    description: '',
                    active: 1,
                },
                errors: {}
            }
        },
        mounted() {
            if (window.ProductCategory !== undefined)
            {
                this.form.id = ProductCategory.id;
                this.form.name = ProductCategory.name;
                this.form.description = ProductCategory.description;
                this.form.active = ProductCategory.active ? 1 : 0;
            }
        },
        methods: {
            createCategory(){
                this.errors = {};
                this.is_busy = true;

                if (this.form.name === '' )
                {
                    this.errors.name = 'Category name is required'
                }

                if (Object.keys(this.errors).length > 0) {
                    this.showError(_.first(Object.keys(this.errors)));
                }
                else {
                    axios.post(this.getDomain('business/' + Business.id + '/product-categories', 'dashboard'), this.form).then(({data}) => {
                        window.location.href = data.redirect_url;
                    }).catch(({response}) => {
                        if (response.status === 422) {
                            _.forEach(response.data.errors, (value, key) => {
                                this.errors[key] = _.first(value);
                            });
                            this.showError(_.first(Object.keys(this.errors)));
                        }
                    });
                }

            },
            showError(firstErrorKey) {
                if (firstErrorKey !== undefined) {
                    this.scrollTo('#' + firstErrorKey);

                    $('#' + firstErrorKey).focus();
                }
                this.is_busy = false;
            },
        }
    }
</script>
