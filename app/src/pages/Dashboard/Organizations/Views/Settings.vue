<template>

	<div class="sc-group--settings">
		<card>
			<h5 slot="header" class="title">Group Settings</h5>
			<form>

				<fg-input type="text"
						  label="Group Name"
						  v-model="group.name">
				</fg-input>

				<div class="form-group has-label">
					<label>Description</label>
					<el-input
						ref="description"
						type="textarea"
						:autosize="{ minRows: 4 }"
						resize="none"
						v-model="group.description"></el-input>
				</div>

				<n-button type="primary" native-type="submit">Save</n-button>

			</form>
		</card>
	</div>

</template>
<script>
  import { Input } from 'element-ui';

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
    props     : {
      groupData: {id: 0},
    },
    data() {
      return {
        group: {
          name       : this.groupData.name,
          description: this.groupData.description.raw
        }
      }
    },
    watch     : {
      'groupData' (to, from) {

        // make sure we are dealing with a new group
        if (to.id === from.id) {
          return;
        }

        this.group = {
          name       : this.groupData.name,
          description: this.groupData.description.raw
        };
      }
    },
    methods   : {}
  }
</script>
<style>
</style>