<template>
	<card class="card-study">
		<div slot="image">
			<router-link :to="link">
				<img class="card-img-top" :src="thumbnail" alt="Card image cap">
			</router-link>
			<div class="actions" v-if="editable">
				<n-button :tag="'a'" :nativeType="'text/html'" :href="'/study-edit/?action=edit&study=' + id" class="dropdown-toggle no-caret" type="neutral" round icon>
					<font-awesome-icon icon="edit"></font-awesome-icon>
				</n-button>
			</div>
		</div>

		<div>
			<h5 class="card-title">
				<router-link :to="link" v-html="title"></router-link>
			</h5>
			<p class="card-text" v-html="description"></p>
		</div>
	</card>
</template>
<script>
  import Card from './Card.vue'

  export default {
    name      : 'study-card',
    components: {
      Card
    },
    props     : {
      link: String,
	  title: String,
	  description: String,
	  id: [String, Number],
	  thumbnail: {
        type: String,
		default: 'data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22320%22%20height%3D%22180%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%20320%20180%22%20preserveAspectRatio%3D%22none%22%3E%3Cdefs%3E%3Cstyle%20type%3D%22text%2Fcss%22%3E%23holder_1618c103586%20text%20%7B%20fill%3Argba(255%2C255%2C255%2C.75)%3Bfont-weight%3Anormal%3Bfont-family%3AHelvetica%2C%20monospace%3Bfont-size%3A16pt%20%7D%20%3C%2Fstyle%3E%3C%2Fdefs%3E%3Cg%20id%3D%22holder_1618c103586%22%3E%3Crect%20width%3D%22320%22%20height%3D%22180%22%20fill%3D%22%23777%22%3E%3C%2Frect%3E%3Cg%3E%3Ctext%20x%3D%22119.0859375%22%20y%3D%2297.35%22%3E320x180%3C%2Ftext%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E'
	  },
	  editable: {
        type: Boolean,
		default: false
	  },
      study: {
        type: Object,
      },
    }
  }

</script>
<style>

</style>
