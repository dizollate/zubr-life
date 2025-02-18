<template>
  <div class="bntu-article-wrapper">
    <div class="bntu-article-wrapper-content">
      <nav class="bntu-article-top">
        <ul>
          <li>
            <router-link :to="{ name: 'feed' }"> Новости </router-link>
          </li>
          <li>
            <img src="/imgs/arrow.svg" alt="arrow" />
          </li>
          <template v-if="data && data.activity">
            <li>
              <span
                class="bntu-description-router"
                v-if="data.activity.title"
                v-html="data.activity.title"
              ></span>
            </li>
          </template>
        </ul>
      </nav>
      <template v-if="data && data.activity">
        <article class="bntu-article">
          <template v-if="!!data.activity.files.length">
            <img
              :src="
                data.activity.files.filter(
                  (item) => item.attachment.type !== 'link'
                )[0].attachment.url
              "
              alt=""
              class="bntu-article-first-img"
            />
          </template>
          <h3 class="is-size-4" v-if="data.activity.title">
            {{ data.activity.title }}
          </h3>
          <p>{{ formatDate(data.activity.created_at) }}</p>
          <p
            style="white-space: pre-wrap; font-size: 18px"
            class="bntu-description-activity"
            v-html="data.activity.description"
          ></p>
          <ul v-if="data.activity.extra.links">
            <li
              v-for="link of data.activity.extra.links"
              :key="link.url ? link.url : link.value"
            >
              <a :href="link.url ? link.url : link.value">{{
                link.name ? link.name : link.url
              }}</a>
            </li>
          </ul>
        </article>
        <div class="wrapper-tabs-bntu" style="min-height: 300px">
          <el-tabs v-model="activeName">
            <el-tab-pane
              label="Комментарии"
              name="comments"
              v-if="data.activity"
            >
              <comments
                :type="'community_activity'"
                :id="data.activity.id"
              ></comments>
            </el-tab-pane>
            <el-tab-pane
              label="Галерея"
              name="media"
              v-if="!!data.activity.files.length"
            >
              <gallery
                :collection="
                  data.activity.files.map((i) => {
                    return i.attachment;
                  })
                "
              ></gallery>
            </el-tab-pane>
            <el-tab-pane
              label="Место"
              name="place"
              v-if="data.activity.geometry"
            >
              <place
                ref="map"
                :feature="data.activity.geometry"
                v-if="mapInit"
              ></place>
            </el-tab-pane>
          </el-tabs>
        </div>
      </template>
    </div>
    <div class="bntu-contacts">
      <div class="bntu-contacts-wrapper">
        <h3 class="bntu-contacts-title">Контакты</h3>
        <div class="bntu-contacts-content">
          <div class="bntu-contacts-stach-wrapper">
            <div class="bntu-contacts-stach">
              <h4 class="bntu-contacts-stach-title">
                Связь с редакцией и стачкомом:
              </h4>
              <span class="bntu-contacts-stach-text"
                >telegram:
                <a
                  href="https://t.me/bntu97_bot"
                  target="_blank"
                  class="bntu-contacts-stach-link"
                  >@bntu97_bot</a
                ></span
              >
            </div>
            <div class="bntu-contacts-stach">
              <h4 class="bntu-contacts-stach-title">
                По вопросам сотрудничества:
              </h4>
              <span class="bntu-contacts-stach-text"
                >bntubelarus@gmail.com</span
              >
            </div>
          </div>
          <div class="bntu-contacts-social">
            <div class="bntu-contacts-stach">
              <h4 class="bntu-contacts-stach-title">Наши соц сети:</h4>
              <div class="bntu-contacts-social-links">
                <a
                  href="https://instagram.com/bntu97"
                  class="bntu-contacts-social-link"
                  target="_blank"
                >
                  <img
                    src="/imgs/bntu/inst.png"
                    alt="instagram bntu97"
                    width="17"
                    height="17"
                  />
                </a>
                <a
                  target="_blank"
                  href="https://t.me/bntu97"
                  class="bntu-contacts-social-link"
                >
                  <img
                    src="/imgs/bntu/tg.png"
                    alt="telegram bntu97"
                    style="margin-left: -5px"
                    width="22"
                    height="18"
                  />
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <footer class="bntu-footer">
      <span class="bntu-footer-text">© 2022 Все права защищены</span>
    </footer>
    <template v-if="data && data.activity === null">
      <span class="pl-5 pb-5">Активность не найдена</span>
      <p>&nbsp;</p>
    </template>
    <template v-if="error">
      <span class="pl-5 pb-5">Произошла ошибка</span>
      <p>&nbsp;</p>
    </template>
    <template v-if="fetching">
      <span class="pl-5 pb-5">Идет загрузка...</span>
      <p>&nbsp;</p>
    </template>
  </div>
</template>

<script>
import { ElTabPane, ElTabs, ElImage } from 'element-plus';
import { useRoute } from 'vue-router';
import { defineComponent, watch, ref } from 'vue';
import Map from '../components/place.vue';
import gallery from '../components/gallery.vue';
import Comments from '../components/comments.vue';
import { useQuery } from '@urql/vue';
import { formatDate } from '../date';

export default defineComponent({
  components: {
    Comments,
    gallery,
    place: Map,
    ElTabPane,
    ElTabs,
    ElImage,
  },
  setup() {
    const result = useQuery({
      // language=GraphQL
      query: `
query ($id: Int!) {
    activity: community_activity_by_pk(id: $id){
        files {
            attachment {
                url
                type: content_type
            }
        }
        id
        description
        content
        title
        category
        geometry
        extra
        created_at
    }
}
      `,
      variables: {
        id: useRoute().params.id,
      },
    });
    const map = ref(null);
    const activeName = ref('comments');
    const mapInit = ref(false);
    watch(activeName, () => {
      if (activeName.value !== 'place' && mapInit.value === true) {
        return;
      }
      mapInit.value = true;
      setTimeout(() => {
        if (!map.value) {
          return;
        }
        map.value.refresh();
      }, 20);
    });
    watch(result.data, (value) => {
      if (!value.activity) {
        return;
      }
      document.title =
        (value.activity.category === 'AD' ? 'Объявление' : 'Событие') +
        ' "' +
        value.activity.description +
        '"' +
        ' - Лошица ZUBR.life';
    });

    return {
      fetching: result.fetching,
      data: result.data,
      error: result.error,
      activeName,
      formatDate,
      mapInit,
      map,
    };
  },
});
</script>
<style>
.bntu-article-wrapper {
  width: 100%;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  padding: 30px 0 0 0;
  margin-top: 110px;
}
.bntu-article-wrapper-content {
  width: 100%;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  max-width: 1060px;
  padding: 0 20px;
}
.bntu-article-top ul {
  display: flex;
  padding: 0;
}
.wrapper-tabs-bntu {
  padding-bottom: 30px;
}
.bntu-article-top ul li {
  margin-right: 10px;
}

.bntu-article-top ul li a {
  font-family: Fira Sans;
  font-style: normal;
  font-weight: 300;
  font-size: 18px;
  line-height: 26px;
  color: #c4c4c4;
  transition: all 0.3s ease;
}
.bntu-article-top ul li a:hover {
  text-decoration: underline;
}

.bntu-article-top ul li span {
  font-family: Fira Sans;
  font-style: normal;
  font-weight: 300;
  font-size: 18px;
  line-height: 26px;
  color: #d32121;
}
.bntu-article-first-img {
  width: 100%;
  max-height: 400px;
  object-fit: cover;
  object-position: top;
}

@media screen and (max-width: 560px) {
  .bntu-article-wrapper {
    margin-top: 60px;
    padding: 0;
  }
  .bntu-article-wrapper-content {
    padding: 0 10px;
  }
  .wrapper-tabs-bntu {
    padding: 0;
  }
}
</style>
