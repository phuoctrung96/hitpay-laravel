<template>
  <b-form-tags
    :value="value"
    @input="$emit('input', $event)"
    size="sm"
    add-on-change
    no-outer-focus
    class="tags-select mb-1 w-100">

    <template v-slot="{ tags, inputAttrs, inputHandlers, disabled, removeTag }">
      <ul v-if="tags.length > 0" class="d-flex flex-wrap pl-0 mb-2">
        <li v-for="tag in tags" :key="tag" class="list-inline-item my-1">
          <b-form-tag
            @remove="removeTag(tag)"
            :title="tag"
            :disabled="disabled">
            <span class="mr-2">{{ optionNames[tag] }}</span>
          </b-form-tag>
        </li>
      </ul>

      <b-form-select
        v-if="availableOptions.length > 0"
        v-bind="inputAttrs"
        v-on="inputHandlers"
        :disabled="disabled"
        :options="availableOptions"
        size="sm">

        <template #first>
          <!-- This is required to prevent bugs with Safari -->
          <option disabled value="">{{ hint }}</option>
        </template>
      </b-form-select>
    </template>
  </b-form-tags>
</template>

<script>
export default {
  name: 'TagsSelect',
  props: {
    value: Array,
    allOptions: Array,
    optionNames: Object,
    hint: String
  },
  computed: {
    availableOptions () {
      return this.allOptions
        .filter(opt => !this.value.includes(opt))
        .map(opt => ({
          value: opt,
          text: this.optionNames[opt]
        }))
    }
  }
}
</script>

<style lang="scss">
.tags-select {
  &.b-form-tags {
    padding: 0;
    border: none;

    .b-form-tag {
      background-color: rgb(218, 222, 227);
      color: rgb(35, 41, 49);
      align-items: center !important;

      font-size: 14px;

      .b-form-tag-remove {
        margin-top: -2px;
        font-size: 22px;
      }
    }
  }
}
</style>
