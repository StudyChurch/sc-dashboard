<template>

	<div class="sc-group--studies">

		<div class="row">
			<div class="col-12">
				<card card-body-classes="table-full-width" no-footer-line>
					<div slot="header">
						<h4 class="card-title">Current Private Studies</h4>
					</div>

					<el-table
						stripe
						style="width: 100%;"
						:show-header="false"
						:data="myStudies">

						<el-table-column width="220">
							<div slot-scope="{row}" class="img-container">
								<router-link :to="$root.cleanLink(row.link)">
									<img :src="row.thumbnail" /></router-link>
							</div>
						</el-table-column>

						<el-table-column min-width="220" key="title" label="Title">
							<div slot-scope="{row}" style="word-break:break-word;">
								<h6>
									<router-link :to="$root.cleanLink(row.link)">{{ row.title | decode }}</router-link>
								</h6>
								<div class="desc-more" :class="{open : true === showDesc[row.id]}">
									<div v-html="row.description" class="desc-more--text"></div>
									<a href="#" class="desc-more--show" @click.prevent="$set(showDesc, row.id, true !== showDesc[row.id])" ></a>
								</div>
							</div>
						</el-table-column>

						<el-table-column
							fixed="right"
							label="Actions"
							width="110">
							<template slot-scope="scope">
								<n-button
									@click.native="removeStudy(scope.row.id)"
									class="remove"
									type="danger"
									size="sm" round icon>
									<i class="fa fa-times"></i>
								</n-button>
							</template>
						</el-table-column>
					</el-table>
				</card>
			</div>
		</div>

		<div class="text-right" v-if="currentUserCan('create_study')">
			<n-button type="primary" @click.native="showModal = true">Create Study</n-button>
		</div>

		<div class="row" v-loading="!tableData.length">
			<div class="col-12">
				<card card-body-classes="table-full-width" no-footer-line>
					<div>
						<div class="col-12 d-flex justify-content-center justify-content-sm-between flex-wrap">
							<fg-input>
								<el-input
									type="search"
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
						<el-table
							stripe
							:show-header="false"
							style="width: 100%;"
							:data="queriedData">

							<el-table-column
								label="Thumb"
								width="220">

								<template slot-scope="scope">
									<img
										v-if="undefined !== scope.row._embedded['wp:featuredmedia']"
										:src="scope.row._embedded['wp:featuredmedia'][0].media_details.sizes.medium.source_url" />
								</template>

							</el-table-column>

							<el-table-column
								key="title"
								style="word-break:break-word;"
								label="Title">
								<div slot-scope="scope" style="word-break:break-word;">
									<h6 v-html="scope.row.title.rendered"></h6>
									<div v-html="scope.row.excerpt.rendered"></div>
								</div>
							</el-table-column>

							<el-table-column
								label="Actions"
								width="75">
								<template slot-scope="scope">
									<n-button
										@click.native="addStudy(scope.row.id)"
										class="add"
										type="primary"
										:disabled="myStudyIDs.includes(scope.row.id.toString()) || myStudyIDs.includes(scope.row.id)"
										size="sm" round icon>
										<i class="fa fa-plus"></i>
									</n-button>
								</template>
							</el-table-column>
						</el-table>
					</div>
					<div slot="footer" class="col-12 d-flex justify-content-center justify-content-sm-between flex-wrap">
						<div class="">
							<p class="card-category">Showing {{from + 1}} to {{to}} of {{total}} entries</p>
						</div>
						<n-pagination
							class="pagination-no-border"
							v-model="pagination.currentPage"
							:per-page="pagination.perPage"
							:total="total">
						</n-pagination>
					</div>
				</card>
			</div>
		</div>

		<modal :show.sync="showModal" headerclasses="justify-content-center" v-if="currentUserCan('create_study')" v-loading="creatingStudy">
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
	</div>

</template>
<script>

  import { Input, Message, Table, TableColumn } from 'element-ui';
  import {
    StudyCard,
    Pagination as NPagination,
    Modal
  } from 'src/components'
  import Fuse from 'fuse.js'
  import { mapState, mapGetters } from 'vuex';

  function getDefaultData () {
    return {
      pagination   : {
        perPage    : 25,
        currentPage: 1,
        total      : 0
      },
      searchQuery  : '',
      searchedData : [],
      fuseSearch   : null,
      loading      : true,
      creatingStudy: false,
      showModal    : false,
      loadingMore  : false,
	  showDesc     : {},
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
      Modal,
      NPagination,
      'el-table'       : Table,
      'el-table-column': TableColumn
    },
    props     : {},
    data      : getDefaultData,
    computed  : {
      ...mapState(['study', 'user']),
      ...mapGetters('user', ['currentUserCan']),
      myStudies() {
        return this.user.me.studies;
      },
      myStudyIDs() {
        return this.myStudies.map(study => study.id);
      },
      tableData() {
        return this.study.studies;
      },

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
    methods   : {
      handleShowDesc(id) {
        Vue.set(this.showDesc, id, true !== tthis.showDesc[id])
	  },
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
      addStudy(id) {
        let studies = this.myStudies.map(study => study.id);
        studies.push(id);
        this.loading = true;

        this.$store
          .dispatch('user/updateUser', {userID: this.user.me.id, data: {studies}})
          .then(() => {
            this.loading = false;
          });
      },
      removeStudy(id) {
        let studies = this.myStudies.map(study => study.id);
        let index = studies.indexOf(id);

        if (index > -1) {
          studies.splice(index, 1);
        } else {
          return;
        }

        this.loading = true;

        this.$store
          .dispatch('user/updateUser', {userID: this.user.me.id, data: {studies}})
          .then(() => {
            this.loading = false;
          });
      }
    },
    watch     : {
      tableData(value) {
        this.searchedData = value;
        this.fuseSearch = new Fuse(this.tableData, {keys: ['title.rendered'], threshold: 0.3})
      },

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