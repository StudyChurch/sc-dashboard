<template>

	<div class="sc-group--studies">
		<div class="text-right">
			<n-button type="primary" @click.native="showModal = true">Create Study</n-button>
		</div>
		<modal :show.sync="showModal" headerclasses="justify-content-center" v-loading="creatingStudy">
			<h4 slot="header" class="title title-up">Create a new study</h4>
			<p>
				<label for="name">Study Name</label>
				<el-input
					ref="name"
					type="text"
					label="Study Name"
					id="name"
					v-model="newStudy.name"></el-input>
			</p>

			<p>
				<label for="name">Study Description</label>
				<el-input
					ref="description"
					type="textarea"
					id="description"
					:autosize="{ minRows: 4 }"
					resize="none"
					label="Study Description"
					v-model="newStudy.description"></el-input>
			</p>
			<template slot="footer">
				<n-button type="primary" @click.native="createStudy">Create</n-button>
			</template>
		</modal>
		<div class="d-flex flex-wrap row">
			<div v-for="study in study.studies" class="col-md-6 d-block">
				<study-card
					:id="study.id"
					:title="study.title.rendered"
					:link="$root.cleanLink(study.link)"
					:description="study.excerpt.rendered"
					:editable="true"
				></study-card>
			</div>
		</div>
	</div>

</template>
<script>

  import { Input, Message } from 'element-ui';
  import {
    StudyCard,
    Modal
  } from 'src/components'
  import { mapState } from 'vuex';

  function getDefaultData () {
    return {
      creatingStudy: false,
      showModal    : false,
      loadingTodos : true,
      loadingMore  : false,
      todoData     : [],
      todoPage     : 1,
      newStudy     : {
        name       : '',
        description: '',
      },
    }
  }

  export default {
    components: {
      StudyCard,
      Input,
      Modal
    },
    props     : {},
    data      : getDefaultData,
    computed  : {
      ...mapState(['study'])
    },
    methods   : {
      createStudy() {
        if (!this.newStudy.name || !this.newStudy.description) {
          Message.error('Please enter a name and description for your new study');
          return;
        }

        this.creatingStudy = true;

        this.$http.post('/wp-json/studychurch/v1/studies/', {
          title  : this.newStudy.name,
          content: this.newStudy.description,
          author : this.$root.userData.id,
          status : 'private',
        })
          .then(response => {
            window.location = '/study-edit/?action=edit&study=' + response.data.id;
            Message.success('Study created! Taking you to the study edit page.');
          })

      },
      reset (keep) {
        let def = getDefaultData();
        def[keep] = this[keep];
        Object.assign(this.$data, def);
      }
    }
  }
</script>
<style>
</style>