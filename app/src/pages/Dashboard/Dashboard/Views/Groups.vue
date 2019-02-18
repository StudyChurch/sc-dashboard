<template>

	<div class="sc-dashboard--groups sc-group">

		<div class="text-right" v-if="canCreateGroup">
			<n-button type="primary" @click.native="showModal = true">Create Group</n-button>
		</div>

		<el-alert type="info" v-if="isOrgOwner" style="margin-bottom: 1rem;">
			<div slot="title">To create a group, go to the church/organization dashboard.</div>
		</el-alert>

		<el-alert type="warning" v-if="showDisabledAlert" style="margin-bottom: 1rem;">
			<div slot="title" v-html="user.me.messages.group_limit"></div>
		</el-alert>

		<modal :show.sync="showModal" headerclasses="justify-content-center" v-loading="creatingGroup" v-if="canCreateGroup">
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

		<el-alert type="info" v-if="!group.groups.length" title="No Groups" description="You are not currently a member of any groups." style="margin-bottom: 1rem;" :closable="false"></el-alert>

	</div>

</template>
<script>
  import { Input } from 'element-ui';
  import { mapState, mapGetters } from 'vuex';

  import {
    Card,
    Button,
    Modal
  } from 'src/components'

  function getDefaultData () {
    return {
      creatingGroup      : false,
      showModal          : false,
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
      ...mapState(['user', 'group']),
      ...mapGetters('user', ['currentUserCan']),
	  isOrgOwner() {
        return this.group.organizations.filter(org => org.creator_id === this.user.me.id).length
	  },
      showDisabledAlert() {
        if (this.canCreateGroup) {
          return false;
        }

        // if this user is the organization owner, don't show a create study button, they should use the organization dashboard
        if (this.isOrgOwner) {
          return false;
        }

        // show if user has reached their limit
        return this.user.me.can.create_group && this.user.me.messages.group_limit;
      },
      canCreateGroup() {
        let count = this.user.me.can.create_group;
        let myGroups = this.group.groups.filter(group => group.creator_id === this.user.me.id);

        // if this user is the organization owner, don't show a create study button, they should use the organization dashboard
        if (this.isOrgOwner) {
          return false;
        }

        if (-1 === count || true === count) {
          return true;
        }

        if (!count) {
          return false;
        }

        return myGroups.length < count;
      },
    },
    methods   : {
      createGroup() {
        if (!this.newGroup.name || !this.newGroup.description) {
          this.$message.error('Please enter a name and description for your new group');
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
            this.creatingGroup = false;
            this.$router.push('/groups/' + group.slug);
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