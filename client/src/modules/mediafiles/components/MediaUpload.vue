<template>
  <label
    for="mediafile_upload"
    class="block text-sm font-medium leading-6 text-gray-900 sm:pt-1.5"
    >{{ __('Upload', 'podlove-podcasting-plugin-for-wordpress') }}</label
  >
  <div class="mt-2 sm:col-span-2 sm:mt-0">
    <div>
      <podlove-button variant="primary" @click="uploadIntent" class="ml-1">
        <upload-icon class="-ml-0.5 mr-2 h-4 w-4" aria-hidden="true" />
        {{ __('Upload Media File', 'podlove-podcasting-plugin-for-wordpress') }}
      </podlove-button>

      <div
        v-if="state.uploadUrl"
        class="mt-4 max-w-xl rounded-md border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700"
      >
        <div class="flex items-start gap-3">
          <document-text-icon class="mt-0.5 h-5 w-5 flex-shrink-0 text-indigo-500" />
          <div class="min-w-0 flex-1">
            <p class="font-medium text-gray-900">
              {{ selectedFilename }}
            </p>
            <p class="mt-1 break-all text-xs text-gray-500">
              {{ state.uploadUrl }}
            </p>
          </div>
          <button
            type="button"
            class="flex-shrink-0 text-gray-400 hover:text-gray-600 focus:outline-none"
            :aria-label="__('Clear selected file', 'podlove-podcasting-plugin-for-wordpress')"
            @click="clearUpload"
          >
            <x-mark-icon class="h-4 w-4" />
          </button>
        </div>

        <div
          v-if="hasSlugMismatch"
          class="mt-3 rounded-md border border-yellow-200 bg-yellow-50 p-3 text-yellow-800"
        >
          <div class="flex gap-2">
            <exclamation-triangle-icon class="mt-0.5 h-5 w-5 flex-shrink-0" />
            <div>
              <p>
                {{
                  __(
                    'Publisher is currently looking for',
                    'podlove-podcasting-plugin-for-wordpress',
                  )
                }}
                <span class="font-medium">{{ expectedFilename }}</span
                >,
                {{ __('but you selected', 'podlove-podcasting-plugin-for-wordpress') }}
                <span class="font-medium">{{ selectedFilename }}</span
                >.
              </p>
              <p v-if="state.slugFrozen" class="mt-1 text-xs">
                {{
                  __(
                    'The filename slug is locked. Use Edit above before changing it.',
                    'podlove-podcasting-plugin-for-wordpress',
                  )
                }}
              </p>
            </div>
          </div>

          <div class="mt-3 flex flex-wrap gap-2">
            <podlove-button
              :variant="state.slugAutogenerationEnabled ? 'primary' : 'secondary'"
              size="small"
              :disabled="state.slugFrozen"
              @click="useSelectedFilename"
            >
              {{ __('Use', 'podlove-podcasting-plugin-for-wordpress') }} {{ selectedSlug }}
              {{ __('as filename', 'podlove-podcasting-plugin-for-wordpress') }}
            </podlove-button>
            <podlove-button
              variant="default"
              size="small"
              @click="keepCurrentSlug"
            >
              {{ __('Keep', 'podlove-podcasting-plugin-for-wordpress') }} {{ state.slug }}
            </podlove-button>
            <podlove-button variant="default" size="small" @click="uploadIntent">
              {{ __('Choose another file', 'podlove-podcasting-plugin-for-wordpress') }}
            </podlove-button>
          </div>
        </div>

        <div v-else class="mt-3 flex items-center gap-2 text-green-700">
          <check-circle-icon class="h-5 w-5 flex-shrink-0" />
          <span>
            {{
              __(
                'Selected filename matches the current file slug.',
                'podlove-podcasting-plugin-for-wordpress',
              )
            }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

import * as mediafiles from '@store/mediafiles.store'
import { State, selectors } from '@store'
import { injectAppDispatch, mapAppState } from '@store/vue'
import PodloveButton from '@components/button/Button.vue'
import {
  CheckCircleIcon,
  CloudArrowUpIcon as UploadIcon,
  DocumentTextIcon,
  ExclamationTriangleIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'

export default defineComponent({
  components: {
    PodloveButton,
    UploadIcon,
    DocumentTextIcon,
    ExclamationTriangleIcon,
    XMarkIcon,
    CheckCircleIcon,
  },
  setup() {
    return {
      state: mapAppState({
        uploadUrl: (state: State) => selectors.mediafiles.wordpressUploadUrl(state),
        slug: (state: State) => selectors.episode.slug(state),
        slugFrozen: (state: State) => selectors.episode.slugFrozen(state),
        slugAutogenerationEnabled: (state: State) =>
          selectors.mediafiles.slugAutogenerationEnabled(state),
      }),
      dispatch: injectAppDispatch(),
    }
  },

  methods: {
    uploadIntent() {
      this.dispatch(mediafiles.uploadIntent())
    },
    clearUpload() {
      this.dispatch(mediafiles.clearUploadUrl())
    },
    useSelectedFilename() {
      this.dispatch(mediafiles.useUploadAsSlug())
    },
    keepCurrentSlug() {
      this.dispatch(mediafiles.keepUploadSlug())
    },
    filenameFromUrl(url: string | null): string {
      if (!url) {
        return ''
      }

      return decodeURIComponent(url.split('?')[0].split('\\').pop()?.split('/').pop() || '')
    },
    slugFromFilename(filename: string): string {
      return filename.split('.').slice(0, -1).join('.')
    },
    extensionFromFilename(filename: string): string {
      const parts = filename.split('.')

      return parts.length > 1 ? parts.pop() || '' : ''
    },
  },

  computed: {
    selectedFilename(): string {
      return this.filenameFromUrl(this.state.uploadUrl)
    },
    selectedSlug(): string {
      return this.slugFromFilename(this.selectedFilename)
    },
    selectedExtension(): string {
      return this.extensionFromFilename(this.selectedFilename)
    },
    expectedFilename(): string {
      const slug = this.state.slug || ''

      return this.selectedExtension ? `${slug}.${this.selectedExtension}` : slug
    },
    hasSlugMismatch(): boolean {
      return !!this.state.slug && !!this.selectedSlug && this.state.slug !== this.selectedSlug
    },
  },
})
</script>
