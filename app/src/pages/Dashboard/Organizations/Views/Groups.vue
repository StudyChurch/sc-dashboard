<template>

	<div class="sc-dashboard--groups sc-group">

		<div class="text-right" v-if="isOrgAdmin()">
			<n-button type="primary" @click.native="handleShowModal">Create Group</n-button>
		</div>

		<modal :show.sync="showModal" headerclasses="justify-content-center" v-loading="loadingModal" v-if="isOrgAdmin()">
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
				<label for="leader">Group Leader</label><br />
				<el-select v-model="newGroup.leader" filterable placeholder="Select group leader">
					<el-option
						v-for="item in members"
						ref="leader"
						id="leader"
						:key="item.value"
						:label="item.label"
						:value="item.value">
					</el-option>
				</el-select>
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

		<div class="row" v-loading="loading">
			<div class="col-12">
				<card card-body-classes="table-full-width" no-footer-line>
					<div>
						<div class="col-12 d-flex justify-content-center justify-content-sm-between flex-wrap">
							<fg-input>
								<el-input type="search"
										  class="mb-3"
										  clearable
										  prefix-icon="el-icon-search"
										  style="width: 200px"
										  placeholder="Search groups"
										  v-model="searchQuery"
										  aria-controls="datatables">
								</el-input>
							</fg-input>

						</div>
						<el-table stripe
								  style="width: 100%;"
								  :data="queriedData">

							<el-table-column min-width="180" key="name" label="Name" prop="name">
								<span slot-scope="{row}" v-html="row.name"></span>
							</el-table-column>

							<el-table-column width="150" key="leader" label="Leader">
								<span slot-scope="{row}" v-html="getName(row.creator_id)"></span>
							</el-table-column>

							<el-table-column width="150" label="Size">
								<template slot-scope="{row}">
									{{ row.members.members.length }}
								</template>
							</el-table-column>

							<el-table-column
								:min-width="135"
								fixed="right"
								label="Actions">
								<div slot-scope="props" class="table-actions">
									<router-link :to="'/groups/' + props.row.slug">View</router-link>
								</div>
							</el-table-column>
						</el-table>
					</div>
					<div slot="footer" class="col-12 d-flex justify-content-center justify-content-sm-between flex-wrap">
						<div class="">
							<p class="card-category">Showing {{from + 1}} to {{to}} of {{total}} entries</p>
						</div>
						<n-pagination class="pagination-no-border"
									  v-model="pagination.currentPage"
									  :per-page="pagination.perPage"
									  :total="total">
						</n-pagination>
					</div>
				</card>
			</div>
		</div>
	</div>
</template>
<script>
  import { Table, TableColumn, Select, Option, Input, Message } from 'element-ui'
  import { Pagination as NPagination, Card, Button, Modal } from 'src/components'
  import users from '../../Tables/users'
  import Fuse from 'fuse.js'
  import swal from 'sweetalert2'
  import { mapState, mapGetters } from 'vuex';

  export default {
    components: {
      Card,
      Button,
      Modal,
      NPagination,
      [Select.name]     : Select,
      [Option.name]     : Option,
      [Table.name]      : Table,
      [TableColumn.name]: TableColumn
    },
    computed  : {
      ...mapState(['user', 'group']),
      ...mapGetters('user', ['getUserById', 'getName']),
      ...mapGetters('group', ['isOrgAdmin', 'isGroupAdmin', 'getOrgMembers']),

      /***
       * Returns a page from the searched data or the whole data. Search is performed in the watch section below
       */
      queriedData () {
        let result = this.tableData;
        result = this.searchedData;
        return result.slice(this.from, this.to)
      },
      to () {
        let highBound = this.from + this.pagination.perPage
        if (this.total < highBound) {
          highBound = this.total
        }
        return highBound
      },
      from () {
        return this.pagination.perPage * (
            this.pagination.currentPage - 1
          )
      },
      total () {
        return this.searchedData.length > 0 ? this.searchedData.length : this.tableData.length;
      }
    },
    data () {
      return {
        loadingModal: false,
        modalLoaded : false,
        showModal   : false,
        loading     : true,
        members     : [],
        newGroup    : {
          name       : '',
          description: '',
          leader     : '',
        },
        pagination  : {
          perPage    : 25,
          currentPage: 1,
          total      : 0
        },
        searchQuery : '',
        tableData   : [],
        searchedData: [],
        fuseSearch  : null
      }
    },
    methods   : {
      createGroup() {
        if (!this.newGroup.name || !this.newGroup.description || !this.newGroup.leader) {
          Message.error('Please enter a name, leader, and description for your new group');
          return;
        }

        this.loadingModal = true;

        this.$store
          .dispatch('group/createGroup', {
            name       : this.newGroup.name,
            description: this.newGroup.description,
            parent_id  : this.group.organization.id,
            user_id    : this.newGroup.leader,
            status     : 'hidden',
          })
          .then(group => {
            this.loadingModal = false;
            this.$router.push('/groups/' + group.slug);
            Message.success('Success! Taking your new group ...');
          })

      },
      handleShowModal() {
        this.showModal = true;

        if (this.modalLoaded) {
          return;
        }

        this.loadingModal = true;

        let members = this.getOrgMembers;

        this.$store
          .dispatch('user/fetchUsersByID', members)
          .then(() => {

            for (let id of this.getOrgMembers) {
              let member = this.getUserById(id);
              this.members.push({
                value: member.id,
                label: member.name,
              });
            }

            this.loadingModal = false;

          });
      },
      handleLike (index, row) {
        swal({
          title             : `You liked ${row.name}`,
          buttonsStyling    : false,
          type              : 'success',
          confirmButtonClass: 'btn btn-success btn-fill'
        })
      },
      handleEdit (index, row) {
        swal({
          title             : `You want to edit ${row.name}`,
          buttonsStyling    : false,
          confirmButtonClass: 'btn btn-info btn-fill'
        });
      },
      handleDelete (index, row) {
        swal({
          title             : 'Are you sure?',
          text              : `You won't be able to revert this!`,
          type              : 'warning',
          showCancelButton  : true,
          confirmButtonClass: 'btn btn-success btn-fill',
          cancelButtonClass : 'btn btn-danger btn-fill',
          confirmButtonText : 'Yes, delete it!',
          buttonsStyling    : false
        }).then((result) => {
          if (result.value) {
            this.deleteRow(row);
            swal({
              title             : 'Deleted!',
              text              : `You deleted ${row.name}`,
              type              : 'success',
              confirmButtonClass: 'btn btn-success btn-fill',
              buttonsStyling    : false
            })
          }
        });
      },
      deleteRow(row){
        let indexToDelete = this.tableData.findIndex((tableRow) => tableRow.id === row.id);
        if (indexToDelete >= 0) {
          this.tableData.splice(indexToDelete, 1)
        }
      }
    },
    created () {
      this.loading = true;
      this.$store
        .dispatch('group/fetchOrgGroups', this.group.organization.id)
        .then(groups => {

          let creators = [];

          for (let group of groups) {
            creators.push(group.creator_id);
          }

          this.$store
            .dispatch('user/fetchUsersByID', creators)
            .then(() => {
              this.loading = false;
              this.searchedData = this.tableData = groups;
              // Fuse search initialization.
              this.fuseSearch = new Fuse(this.tableData, {keys: ['name', 'leader'], threshold: 0.3})
            });

        });
    },
    watch     : {
      /**
       * Searches through the table data by a given query.
       * NOTE: If you have a lot of data, it's recommended to do the search on the Server Side and only display the results here.
       * @param value of the query
       */
      searchQuery(value){
        let result = this.tableData;
        if (value !== '') {
          result = this.fuseSearch.search(this.searchQuery)
        }
        this.searchedData = result;
      }
    }
  }
</script>
<style>
</style>
