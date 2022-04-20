<template>
  <div class="card-reader d-flex flex-column p-3">
    <span class="title">Card Reader</span>
    <span class="info-text">Order card reader to start accepting contactless payments</span>

    <div class="d-flex justify-content-between">
      <img src="/icons/reader/reader.png"/>
      <a href="https://myshop.sg/hitpayterminal" target="_blank" class="align-self-end">
        <button class="btn btn-primary">
          Order Now
        </button>
      </a>
    </div>
  </div>  
</template>

<script>
export default {
  name: 'CardReader'
}
</script>

<style lang="scss" scoped>
.card-reader {
  width: 100%;
  height: 238px;
  background: linear-gradient(#9AC4F8, #4A90E2);
  border-radius: 6px;

  .title {
    color: white;
    font-size: 18px;
    font-weight: 600;
  }

  .info-text {
    font-size: 14px;
    line-height: 1.14;
    padding: 10px 0px 10px 0px;
    // max-width: 165px;
  }

  button {
    position: relative;
    top: -10px;
  }

  // $buttonColor: #262654;

  // button {
  //   color: white;
  //   background-color: $buttonColor;
  //   border-radius: 6px;
  //   border: 0;
  //   height: 27px;
  //   margin-bottom: 9px;
  //   width: 86px;
  //   font-size: 12px;

  //   &:hover {
  //     background-color: lighten($buttonColor, 10%);
  //   }

  //   &:focus {
  //     outline: none;
  //   }
  // }
}
</style>