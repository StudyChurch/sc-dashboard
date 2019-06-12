<template>

	<div class="sc-group--studies">

		<div class="row">
			<div class="col-12">
				<div class="text-right" v-if="isGroupAdmin() && ( currentUserCan( 'create_study' ) ) || groupOrgID )">
					<n-button type="primary" @click.native="handleShowModal">Create Study</n-button>
				</div>

				<modal :show.sync="showModal" headerclasses="justify-content-center" v-if="isGroupAdmin() && ( currentUserCan( 'create_study' ) ) || groupOrgID )" v-loading="creatingStudy">
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

				<card card-body-classes="table-full-width" no-footer-line>
					<div slot="header">
						<h4 class="card-title">Group Studies</h4>
					</div>

					<el-table
						stripe
						style="width: 100%;"
						:show-header="false"
						:data="groupStudies">

						<el-table-column width="150">
							<div slot-scope="{row}" class="img-container">
								<router-link :to="$root.cleanLink(row.link)">
									<img :src="row.thumbnail" /></router-link>
							</div>
						</el-table-column>

						<el-table-column min-width="220" key="title" label="Title">
							<div slot-scope="{row}" style="word-break:break-word;" v-loading="loading[row.id]">
								<h6>
									<router-link :to="'/groups/' + groupData.slug + $root.cleanLink(row.link)">{{ row.title.rendered | decode }}</router-link>
								</h6>
								<div class="desc-more" :class="{open : true === showDesc[row.id]}">
									<div v-html="row.excerpt.rendered" class="desc-more--text"></div>
									<a href="#" class="desc-more--show" @click.prevent="$set(showDesc, row.id, true !== showDesc[row.id])"></a>
								</div>
							</div>
						</el-table-column>

						<el-table-column
							fixed="right"
							align="right"
							label="Actions"
							v-if="isGroupAdmin()"
							width="110">
							<template slot-scope="scope">
								<a :href="'/studio/studies/' + scope.row.id" v-if="scope.row.author === user.me.id">
									<n-button
										class="edit btn-neutral"
										type="info"
										size="sm" icon>
										<font-awesome-icon icon="edit"></font-awesome-icon>
									</n-button>
								</a>
								<n-button
									@click.native="removeStudy(scope.row.id)"
									class="remove btn-neutral"
									type="danger"
									size="sm" icon>
									<font-awesome-icon icon="times"></font-awesome-icon>
								</n-button>
							</template>
						</el-table-column>
					</el-table>
				</card>
			</div>
		</div>

		<div class="row" v-loading="!tableData.length" v-if="isGroupAdmin()">
			<div class="col-12">
				<card card-body-classes="table-full-width" no-footer-line>

					<div slot="header">
						<div class="d-flex justify-content-center justify-content-sm-between flex-wrap">
							<h4 class="card-title">Study Library</h4>
							<fg-input>
								<el-input
									type="search"
									class="mb-3"
									clearable
									prefix-icon="el-icon-search"
									style="width: 200px"
									placeholder="Search library"
									v-model="searchQuery"
									aria-controls="datatables">
								</el-input>
							</fg-input>
						</div>
						<el-select class="select-primary" placeholder="All Studies" v-model="filter.scope">
							<el-option class="select-primary" :value="'library'" :label="'Group Library'" :key="'library'"></el-option>
							<el-option class="select-primary" :value="'mine'" :label="'My Studies'" :key="'mine'" v-if="authoredStudies.length"></el-option>
							<el-option class="select-primary" :value="'premium'" :label="'Premium Studies'" :key="'premium'" v-if="premiumStudies.length"></el-option>
							<el-option class="select-primary" :value="'all'" :label="'All Studies'" :key="'all'" v-if="premiumStudies.length"></el-option>
						</el-select>
						&nbsp;
						<el-select class="select-primary" placeholder="All Categories" v-model="filter.category" v-if="studyCategories.length">
							<el-option class="select-primary" :value="'all'" :label="'All Categories'" :key="'all'"></el-option>
							<el-option v-for="option in studyCategories" class="select-primary" :value="option.slug" :label="option.name" :key="option.slug"></el-option>
						</el-select>

						<hr />
					</div>


					<div>
						<el-table
							stripe
							:show-header="false"
							style="width: 100%;"
							:data="queriedData">

							<el-table-column
								label="Thumb"
								width="150">

								<template slot-scope="scope">
									<img :src="scope.row.thumbnail" />
								</template>

							</el-table-column>

							<el-table-column
								key="title"
								style="word-break:break-word;"
								label="Title">
								<div slot-scope="{row}" style="word-break:break-word;" v-loading="loading[row.id]">
									<h6>
										<router-link :to="'/groups/' + groupData.slug + $root.cleanLink(row.link)">{{ row.title.rendered | decode }}</router-link>
									</h6>
									<div class="desc-more" :class="{open : true === showLibDesc[row.id]}">
										<div v-html="row.excerpt.rendered" class="desc-more--text"></div>
										<a href="#" class="desc-more--show" @click.prevent="$set(showLibDesc, row.id, true !== showLibDesc[row.id])"></a>
									</div>
								</div>
							</el-table-column>

							<el-table-column
								fixed="right"
								label="Actions"
								align="right"
								width="135">
								<template slot-scope="scope">
									<a :href="'/studio/studies/' + scope.row.id" v-if="scope.row.author === user.me.id">
										<n-button
											class="edit btn-neutral"
											type="info"
											size="sm" icon>
											<font-awesome-icon icon="edit"></font-awesome-icon>
										</n-button>
									</a>
									<n-button
										@click.native="addStudy(scope.row.id)"
										class="add btn-neutral"
										type="primary"
										:disabled="myStudyIDs.includes(scope.row.id.toString()) || myStudyIDs.includes(scope.row.id)"
										v-if="canAccessStudy(scope.row)"
										size="sm" icon>
										<font-awesome-icon icon="plus"></font-awesome-icon>
									</n-button>
									<n-button
										class="add btn-neutral"
										type="primary"
										:href="getStudyPurchaseLink(scope.row)"
										:nativeType="'text/html'"
										tag="a"
										size="sm"
										v-else>
										<font-awesome-icon icon="dollar-sign"></font-awesome-icon>
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
			<div v-if="0" class="col-12">
				<card card-body-classes="table-full-width" no-footer-line>
					<div slot="header" class="col-12 d-flex justify-content-center justify-content-sm-between flex-wrap">
						<h4 class="card-title">All Studies</h4>
						<fg-input>
							<el-input
								type="search"
								class="mb-3"
								clearable
								prefix-icon="el-icon-search"
								style="width: 200px"
								placeholder="Search library"
								v-model="searchQuery"
								aria-controls="datatables">
							</el-input>
						</fg-input>

					</div>

					<div>
						<el-table
							stripe
							:show-header="false"
							style="width: 100%;"
							:data="queriedData">

							<el-table-column
								label="Thumb"
								width="150">

								<template slot-scope="scope">
									<img :src="scope.row.thumbnail" />
								</template>

							</el-table-column>

							<el-table-column
								key="title"
								style="word-break:break-word;"
								label="Title">
								<div slot-scope="{row}" style="word-break:break-word;" v-loading="loading[row.id]">
									<h6>
										{{ row.title.rendered | decode }}
									</h6>
									<div class="desc-more" :class="{open : true === showLibDesc[row.id]}">
										<div v-html="row.excerpt.rendered" class="desc-more--text"></div>
										<a href="#" class="desc-more--show" @click.prevent="$set(showLibDesc, row.id, true !== showLibDesc[row.id])"></a>
									</div>
								</div>
							</el-table-column>

							<el-table-column
								fixed="right"
								label="Actions"
								align="right"
								width="135">
								<template slot-scope="scope">
									<a :href="'/studio/studies/' + scope.row.id" v-if="scope.row.author === user.me.id">
										<n-button
											class="edit btn-neutral"
											type="info"
											size="sm" icon>
											<font-awesome-icon icon="edit"></font-awesome-icon>
										</n-button>
									</a>
									<n-button
										@click.native="addStudy(scope.row.id)"
										class="add btn-neutral"
										type="primary"
										:disabled="myStudyIDs.includes(scope.row.id.toString()) || myStudyIDs.includes(scope.row.id)"
										v-if="canAccessStudy(scope.row)"
										size="sm" icon>
										<font-awesome-icon icon="plus"></font-awesome-icon>
									</n-button>
									<n-button
										class="add btn-neutral"
										type="primary"
										:href="getStudyPurchaseLink(scope.row)"
										:nativeType="'text/html'"
										tag="a"
										size="sm"
										v-else>
										<font-awesome-icon icon="dollar-sign"></font-awesome-icon>
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


	</div>

</template>
<script>
  import { Input, Message, Table, TableColumn, Select, Option } from 'element-ui';
  import {
    StudyCard,
    Pagination as NPagination,
    Modal
  } from 'src/components'
  import Fuse from 'fuse.js'
  import { mapState, mapGetters } from 'vuex';

  function getDefaultData () {
    return {
      pagination    : {
        perPage    : 25,
        currentPage: 1,
        total      : 0
      },
      filter        : {
        scope   : 'library',
        category: 'all',
      },
      searchQuery   : '',
      searchedData  : [],
      fuseSearch    : null,
      loading       : {},
      creatingStudy : false,
      showModal     : false,
      showDesc      : {},
      showLibDesc   : {},
      newStudy      : {
        name       : '',
        description: '',
      },
      loadingStudies: true,
      loadingMore   : false,
      studies       : [],
      todoData      : [],
    }
  }

  export default {
    components: {
      StudyCard,
      Input,
      Modal,
      NPagination,
      Message,
      'el-select'      : Select,
      'el-option'      : Option,
      'el-table'       : Table,
      'el-table-column': TableColumn
    },
    data      : getDefaultData,
    computed  : {
      ...mapState(['study', 'user', 'group']),
      ...mapGetters('user', ['currentUserCan']),
      ...mapGetters('group', ['isOrgAdmin', 'isGroupAdmin']),

      groupOrgID() {
        return this.groupData.parent_id;
      },

      groupStudies() {
        return this.groupData.studies;
      },

      myStudyIDs() {
        return this.groupStudies.map(study => study.id);
      },

      groupData() {
        return this.group.group;
      },

      /**
       * Data to use for the table
       */
      tableData() {
        let studies = this.groupOrgID ? this.group.organization.studies : this.study.studies;
        return studies.filter(study => study.status === 'publish' || study.author === this.user.me.id);
      },

      /**
       * Return studies authored by the current user
       */
      authoredStudies() {
        return this.tableData.filter(study => study.author === this.user.me.id);
      },

      /**
       * Return premium studies list
       */
      premiumStudies() {
        return this.tableData.filter(study => study.restrictions !== null && study.restrictions.length !== 0);
      },

      /**
       * Get all the categories for current view
       */
      studyCategories() {
        let categories = [];
        let study = {};

        for (let i = 0; i < this.filteredScopeData.length; i++) {
          study = this.filteredScopeData[i];

          for (let x = 0; x < study.categories.length; x++) {
            if (!categories.filter(cat => cat.slug === study.categories[x].slug).length) {
              categories.push(study.categories[x]);
            }
          }
        }

        return categories;
      },

      /**
       * Returns a page from the searched data or the whole data. Search is performed in the watch section below
       */
      queriedData () {
        let result = this.searchedData;
        return result.slice(this.from, this.to)
      },

      filteredScopeData() {
        switch (this.filter.scope) {
          case 'premium' :
            return this.premiumStudies;
          case 'mine' :
            return this.authoredStudies;
          case 'library' :
            return this.tableData.filter(study => this.canAccessStudy(study));
          default :
            return this.tableData;
        }
      },

      /**
       * The upper limit of the current page view
       */
      to () {
        let highBound = this.from + this.pagination.perPage
        if (this.total < highBound) {
          highBound = this.total
        }
        return highBound
      },

      /**
       * The lower limit of the current page view
       */
      from () {
        return this.pagination.perPage * (
            this.pagination.currentPage - 1
          )
      },

      /**
       * The total number of studies
       */
      total () {
        return this.searchedData.length > 0 ? this.searchedData.length : this.tableData.length;
      },

    },
    watch     : {
      tableData(value) {
        this.searchedData = this.filteredScopeData;
        this.fuseSearch = new Fuse(this.tableData, {keys: ['title.rendered', 'slug'], threshold: 0.3})
      },

      /**
       * Searches through the table data by a given query.
       * NOTE: If you have a lot of data, it's recommended to do the search on the Server Side and only display the results here.
       * @param value of the query
       */
      searchQuery(value){
        let result = this.tableData;
        this.filter.scope = this.premiumStudies.length ? 'all' : 'library';
        if (value !== '') {
          result = this.fuseSearch.search(this.searchQuery)
        }
        this.searchedData = result;
      },

      'filter.scope' () {
        this.filter.category = 'all';
        this.searchedData = this.filteredScopeData;
      },

      'filter.category' (value) {
        switch (value) {
          case 'all' :
            this.searchedData = this.filteredScopeData;
            break;
          default :
            this.searchedData = this.filteredScopeData.filter(
              study => study.categories.filter(cat => cat.slug === value).length);
        }
      },

    },
    methods   : {
      addStudy(id) {
        let studies = this.groupStudies.map(study => study.id);
        studies.push(id);
        this.$set(this.loading, id, true);

        return this.$store
          .dispatch('group/updateGroup', {groupID: this.groupData.id, data: {studies}})
          .then(() => {
            this.$set(this.loading, id, false);
          })
      },
      removeStudy(id) {
        let studies = this.groupStudies.map(study => study.id);
        let index = studies.indexOf(id);

        if (index > -1) {
          studies.splice(index, 1);
        } else {
          return;
        }

        this.$set(this.loading, id, true);

        this.$store
          .dispatch('group/updateGroup', {groupID: this.groupData.id, data: {studies}})
          .then(() => {
            this.$set(this.loading, id, false);
          });
      },
      canAccessStudy(study) {
        if (!Boolean(study.restrictions) || !study.restrictions.length) {
          return true;
        }

        return this.group.group.premium_access.filter(
          access => -1 !== study.restrictions.indexOf(access)).length;
      },
      getStudyPurchaseLink(study) {
        return '/library/?sc_premium=' + study.restrictions.join(',');
      },
      handleShowModal() {
        this.showModal = true;
      },
      createStudy() {

            if (!this.newStudy.name || !this.newStudy.description) {
                Message.error('Please enter a name and description for your new study');
                return;
            }

            this.creatingStudy = true;

            let data = {
                title   : this.newStudy.name,
                content : this.newStudy.description,
                author  : this.user.me.id,
                status  : 'private',
			};

            if ( this.groupOrgID ) {
                data.sc_group = [ this.groupOrgID + '' ];
			}

            this.$store
                .dispatch('study/createStudy', data )
                .then(response => {
                this.addStudy(response.id)
                .then(() => {
                window.location = '/studio/studies/' + response.id;
            Message.success('Study created! Taking you to the study edit page.');
        })
        })

      },
    },
    mounted() {
      this.searchedData = this.filteredScopeData;
      this.fuseSearch = new Fuse(this.tableData, {keys: ['title.rendered', 'slug'], threshold: 0.3})
    }
  }
</script>
<style>
</style>