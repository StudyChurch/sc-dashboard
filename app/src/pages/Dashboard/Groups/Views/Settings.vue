<template>

	<div class="sc-group--settings" v-loading="loading">
		<card>
			<h5 slot="header" class="title">Settings</h5>

			<fg-input type="text"
					  label="Name"
					  v-model="groupSettings.name">
			</fg-input>

			<div class="form-group has-label">
				<label>Description</label>
				<el-input
					ref="description"
					type="textarea"
					:autosize="{ minRows: 4 }"
					resize="none"
					v-model="groupSettings.description"></el-input>
			</div>

			<n-button type="primary" @click.native="updateGroup">Save</n-button>

		</card>
	</div>

</template>
<script>
  import { Input } from 'element-ui';
  import { mapState } from 'vuex';

  import {
    Card,
    Table as NTable,
    Button
  } from 'src/components'

  export default {
    components: {
      Card,
      NTable,
      Button,
      Input
    },
    data() {
      return {
        loading      : false,
        groupSettings: {
          name       : '',
          description: ''
        }
      }
    },
    computed  : {
      ...mapState(['group', 'user']),
      groupData() {
        return this.group.group;
      }
    },
    watch     : {
      'groupData' (to, from) {

        // make sure we are dealing with a new group
        if (to.id === from.id) {
          return;
        }

        this.groupSettings = {
          name       : this.groupData.name,
          description: this.groupData.description.raw
        };
      }
    },
    mounted() {
      this.groupSettings = {
        name       : this.groupData.name,
        description: this.groupData.description.raw
      };
    },
    methods   : {
      updateGroup() {
        this.loading = true;
        return this.$store
          .dispatch('group/updateGroup', {groupID: this.groupData.id, data: this.groupSettings})
          .then(() => {
            this.loading = false;
          })
      }
    }
  }
</script>
<style>
</style>