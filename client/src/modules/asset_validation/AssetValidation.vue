<template>
  <div id="asset_validation">
    <button
      id="revalidate_assets"
      type="button"
      class="button button-primary"
      :disabled="isVerifying"
      @click="verifyAll"
    >
      {{ __('Revalidate Assets', domain) }}
    </button>

    <table id="asset_status_dashboard" class="asset-validation-table">
      <thead>
        <tr>
          <th>{{ __('Episode', domain) }}</th>
          <th v-for="asset in assets" :key="asset.id">
            {{ asset.title }}
          </th>
          <th>{{ __('Status', domain) }}</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="episode in episodes" :key="episode.id">
          <td>
            <a :href="episode.edit_url">
              <i v-if="episode.slug_missing" class="podlove-icon-minus"></i>
              <span v-if="episode.slug_missing"> </span>
              {{ episode.label }}
            </a>
          </td>
          <td v-for="asset in assets" :key="asset.id" class="media_file_status">
            <button
              v-if="fileFor(episode, asset.id)"
              type="button"
              class="asset-validation-status"
              :title="__('Verify media file', domain)"
              :disabled="fileFor(episode, asset.id)?.is_verifying"
              @click="verifyFile(episode, asset.id)"
            >
              <i :class="statusIcon(fileFor(episode, asset.id))"></i>
              <span class="screen-reader-text">{{ __('Verify media file', domain) }}</span>
            </button>
            <i v-else class="podlove-icon-minus"></i>
          </td>
          <td>{{ episode.status }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { __ } from '../../plugins/translations'

type AssetValidationAsset = {
  id: number
  title: string
}

type AssetValidationFile = {
  asset_id: number
  media_file_id: number
  active: boolean
  size: number
  url?: string
  is_verifying?: boolean
  has_error?: boolean
}

type AssetValidationEpisode = {
  id: number
  post_id: number
  label: string
  slug_missing: boolean
  edit_url: string
  status: string
  files: AssetValidationFile[]
}

type AssetValidationData = {
  assets?: AssetValidationAsset[]
  episodes?: AssetValidationEpisode[]
}

type ApiData = {
  base?: string
  nonce?: string
}

const domain = 'podlove-podcasting-plugin-for-wordpress'

const podloveData = () => (window as any).PODLOVE_DATA || {}

export default defineComponent({
  data() {
    const data: AssetValidationData = podloveData().asset_validation || {}

    return {
      domain,
      assets: data.assets || [],
      episodes: (data.episodes || []).map((episode) => ({
        ...episode,
        files: (episode.files || []).map((file) => ({
          ...file,
          active: !!file.active,
          size: Number(file.size || 0),
          is_verifying: false,
          has_error: false,
        })),
      })),
    }
  },

  computed: {
    isVerifying(): boolean {
      return this.episodes.some((episode: AssetValidationEpisode) =>
        episode.files.some((file) => file.is_verifying)
      )
    },
  },

  methods: {
    __,

    api(): ApiData {
      return podloveData().api || {}
    },

    apiBase(): string {
      return (this.api().base || '').replace(/\/$/, '')
    },

    fileFor(episode: AssetValidationEpisode, assetId: number): AssetValidationFile | undefined {
      return episode.files.find((file) => file.asset_id === assetId)
    },

    statusIcon(file?: AssetValidationFile): string[] {
      if (!file) {
        return ['podlove-icon-minus']
      }

      if (file.is_verifying) {
        return ['podlove-icon-spinner', 'rotate']
      }

      if (file.has_error) {
        return ['clickable', 'podlove-icon-remove']
      }

      if (!file.active) {
        return ['podlove-icon-minus']
      }

      if (file.size <= 0) {
        return ['clickable', 'podlove-icon-remove']
      }

      return ['clickable', 'podlove-icon-ok']
    },

    async verifyAll() {
      await Promise.all(
        this.episodes.flatMap((episode: AssetValidationEpisode) =>
          episode.files.map((file) => this.verifyFile(episode, file.asset_id))
        )
      )
    },

    async verifyFile(episode: AssetValidationEpisode, assetId: number) {
      const file = this.fileFor(episode, assetId)

      if (!file || file.is_verifying || !this.apiBase()) {
        return
      }

      file.is_verifying = true
      file.has_error = false

      try {
        const response = await fetch(`${this.apiBase()}/v2/episodes/${episode.id}/media/${assetId}/verify`, {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-WP-Nonce': this.api().nonce || '',
          },
          body: JSON.stringify({}),
        })

        let result: any = {}
        try {
          result = await response.json()
        } catch (e) {
          result = {}
        }

        if (!response.ok) {
          throw result
        }

        file.active = typeof result.active === 'boolean' ? result.active : file.active
        file.size = Number(result.file_size || 0)
        file.url = result.file_url || file.url
      } catch (e) {
        file.has_error = true
      } finally {
        file.is_verifying = false
      }
    },
  },
})
</script>

<style scoped>
.asset-validation-table {
  margin-top: 1em;
}

.media_file_status {
  text-align: center;
  font-weight: bold;
  font-size: 20px;
}

.asset-validation-status {
  padding: 0;
  border: 0;
  background: transparent;
  color: inherit;
  cursor: pointer;
  font: inherit;
}

.asset-validation-status:disabled {
  cursor: default;
}
</style>
