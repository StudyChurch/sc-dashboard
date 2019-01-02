<template>

	<div class="sc-group--members">

		<div class="text-right">
			<n-button type="primary" @click.native="addMember" v-if="isOrgAdmin()">Add Member</n-button>
		</div>

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
										  placeholder="Search members"
										  v-model="searchQuery"
										  aria-controls="datatables">
								</el-input>
							</fg-input>

						</div>
						<el-table stripe
								  style="width: 100%;"
								  :data="queriedData">

							<el-table-column width="80px" prop="thumb">
								<div slot-scope="{row}">
									<img :src="row.thumb" class="avatar" />
								</div>
							</el-table-column>

							<el-table-column min-width="180" key="name" label="Name" prop="name"></el-table-column>

							<el-table-column width="150" label="Role" prop="role">
								<template slot-scope="{row}">
									<el-select class="select-default" v-model="row.role" v-if="isOrgAdmin() && row.id !== group.organization.creator_id" @change="handleRoleChange(row.id, row)">
										<el-option class="select-default" value="Leader" label="Admin"></el-option>
										<el-option class="select-default" value="Member" label="Member"></el-option>
									</el-select>
									<span v-else>{{ row.role }}</span>
								</template>
							</el-table-column>

							<el-table-column
								v-if="isOrgAdmin()"
								fixed="right"
								label="Remove">
								<div slot-scope="props" class="table-actions">
									<n-button v-if="props.row.id !== group.organization.creator_id" @click.native="handleDelete(props.$index, props.row)"
											  class="remove"
											  type="danger"
											  size="sm" round icon>
										<i class="fa fa-times"></i>
									</n-button>
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
  import {
    Card,
    Button,
    Pagination as NPagination,
  } from 'src/components'
  import Fuse from 'fuse.js'
  import swal from 'sweetalert2'
  import { MessageBox, Table, TableColumn, Select, Option } from 'element-ui';
  import { mapState, mapGetters } from 'vuex';

  export default {
    components: {
      Card,
      Button,
      NPagination,
      [Select.name]     : Select,
      [Option.name]     : Option,
      [Table.name]      : Table,
      [TableColumn.name]: TableColumn
    },
    props     : {
      groupData: {
        default: {
          id     : 0,
          studies: []
        }
      },
    },
    data() {
      return {
        pagination  : {
          perPage    : 25,
          currentPage: 1,
          total      : 0
        },
        searchQuery : '',
        tableData   : [],
        searchedData: [],
        fuseSearch  : null,
        loading     : true,
        admins      : [],
        members     : [],
      }
    },
    computed  : {
      ...mapState(['user', 'group']),
      ...mapGetters('user', ['getUserById']),
      ...mapGetters('group', ['isOrgAdmin', 'isGroupAdmin', 'getOrgMembers', 'getOrgAdmins']),

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
    created() {
      let members = this.getOrgMembers;
      this.loading = true;
      this.$store
        .dispatch('user/fetchUsersByID', members)
        .then(() => {
          this.loading = false;

          for (let id of this.getOrgMembers) {
            let member = this.getUserById(id);
            this.tableData.push({
              id   : member.id,
              thumb: member.avatar_urls.thumb,
              name : member.name,
              email: member.email,
              role : this.getMemberRole(id),
            });

          }

          this.searchedData = this.tableData;
          this.fuseSearch = new Fuse(this.tableData, {keys: ['thumb', 'name'], threshold: 0.3})

        })
    },
    methods   : {
      addMember() {
        let self = this;
        MessageBox.prompt('Use this link to invite members to join this group.', 'Add a Member', {
          dangerouslyUseHTMLString: true,
          inputValue              : self.groupData.invite,
          inputType               : 'textarea',
          showCancelButton        : false
        }).then().catch();
      },
      getMemberRole(userID) {
        return this.getOrgAdmins.includes(userID) ? 'Leader' : 'Member';
      },
      handleRoleChange (userID, row) {
        if (row.role === this.getMemberRole(userID)) {
          return;
        }

        this.loading = true;

        let action = (
          row.role === 'Member'
        ) ? 'group/demoteUser' : 'group/upgradeUser';

        this.$store
          .dispatch(action, {userID, groupID: this.group.organization.id})
          .then(() => {
            this.loading = false;
          });

      },
      handleDelete (index, row) {
        let thisVue = this;
        swal({
          title              : 'Are you sure?',
          text               : `You won't be able to revert this!`,
          type               : 'warning',
          showCancelButton   : true,
          confirmButtonText  : 'Yes, delete it!',
          showLoaderOnConfirm: true,
          preConfirm         : () => {
            return this.$store
              .dispatch('group/removeUser', {userID: row.id, groupID: this.group.organization.id})
              .then(() => {
                this.deleteRow(row);
              });
          }
        }).then((result) => {
          if (result.value) {
            swal({
              title             : 'Removed!',
              text              : `You removed ${row.name}`,
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