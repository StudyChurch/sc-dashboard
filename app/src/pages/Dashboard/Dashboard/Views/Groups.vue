<template>

	<div class="sc-dashboard--groups sc-group">

		<div class="text-right">
			<n-button type="primary" @click.native="showModal = true">Create Group</n-button>
		</div>
		<modal :show.sync="showModal" headerclasses="justify-content-center" v-loading="creatingGroup">
			<h4 slot="header" class="title title-up">Create a new group</h4>
			<p>
				<label for="name">Group Name</label>
				<el-input
					ref="name"
					type="text"
					label="Study Name"
					id="name"
					v-model="newGroup.name"></el-input>
			</p>

			<p>
				<label for="name">Group Description</label>
				<el-input
					ref="description"
					type="textarea"
					id="description"
					:autosize="{ minRows: 4 }"
					resize="none"
					label="Study Description"
					v-model="newGroup.description"></el-input>
			</p>
			<template slot="footer">
				<n-button type="primary" @click.native="createGroup">Create</n-button>
			</template>
		</modal>
		<div class="d-flex flex-wrap row">
			<router-link v-for="group in group.groups" :key="group.id" class="col-md-6 d-block" :to="$root.cleanLink(group.link)">
				<card class="card-user" style="padding: 0;">
					<div slot="image" class="image">
						<img src="@/assets/img/bg-bible.jpg" alt="...">
					</div>
					<div>
						<div class="author">
							<font-awesome-icon icon="users" class="avatar border-gray"></font-awesome-icon>

							<h5 class="title" v-html="group.name"></h5>
							<p class="description" v-show="showGroupDesc" v-html="group.description.rendered"></p>
						</div>
					</div>
				</card>
			</router-link>
		</div>

	</div>

</template>
<script>
  import { Input, Message } from 'element-ui';
  import { mapState } from 'vuex';

  import {
    Card,
    Button,
    Modal
  } from 'src/components'

  function getDefaultData () {
    return {
      creatingGroup      : false,
      showModal          : false,
      loading            : true,
      loadingMoreActivity: false,
      activityData       : [],
      activityPage       : 1,
      showGroupDesc      : false,
      newGroup           : {
        name       : '',
        description: '',
      },

    }
  }

  export default {
    components: {
      Card,
      Button,
      Modal
    },
    data      : getDefaultData,
    mounted() {
    },
    computed  : {
      ...mapState(['user', 'group'])
    },
    methods   : {
      createGroup() {
        if (!this.newGroup.name || !this.newGroup.description) {
          Message.error('Please enter a name and description for your new group');
          return;
        }

        this.creatingGroup = true;

        this.$store
          .dispatch('group/createGroup', {
            name       : this.newGroup.name,
            description: this.newGroup.description,
            user_id    : this.user.me.id,
            status     : 'hidden',
          })
          .then(group => {
            console.log(group);
            this.creatingGroup = false;
            this.$router.push('/groups/' + group.slug);
            Message.success('Success! Taking your new group ...');
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