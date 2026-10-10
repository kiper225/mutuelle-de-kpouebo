<template>
  <!-- File Upload -->
  <div data-hs-file-upload='{
    "url": "/upload",
    "acceptedFiles": "image/*",
    "autoHideTrigger": false,
    "extensions": {
      "default": {
        "class": "shrink-0 size-5"
      },
      "xls": {
        "class": "shrink-0 size-5"
      },
      "zip": {
        "class": "shrink-0 size-5"
      },
      "csv": {
        "icon": "<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"24\" height=\"24\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"><path d=\"M4 22h14a2 2 0 0 0 2-2V7l-5-5H6a2 2 0 0 0-2 2v4\"/><path d=\"M14 2v4a2 2 0 0 0 2 2h4\"/><path d=\"m5 12-3 3 3 3\"/><path d=\"m9 18 3-3-3-3\"/></svg>",
        "class": "shrink-0 size-5"
      }
    }
  }'>
    <template data-hs-file-upload-preview>
      <div class="relative mt-2 p-2 bg-white border border-gray-200 rounded-xl dark:bg-neutral-800 dark:border-neutral-700">
        <img class="mb-2 w-full object-cover rounded-lg" src="data:," alt="" data-dz-thumbnail>

        <div class="mb-1 flex justify-between items-center gap-x-3 whitespace-nowrap">
          <div class="w-10">
            <span class="text-sm text-foreground">
              <span data-hs-file-upload-progress-bar-value>0</span>%
            </span>
          </div>

          <div class="flex items-center gap-x-2">
            <button type="button" class="text-muted-foreground-1 hover:text-foreground focus:outline-hidden focus:text-foreground" data-hs-file-upload-remove>
              <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"></path><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path><line x1="10" x2="10" y1="11" y2="17"></line><line x1="14" x2="14" y1="11" y2="17"></line></svg>
            </button>
          </div>
        </div>

        <div class="flex w-full h-2 bg-surface-1 rounded-full overflow-hidden" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" data-hs-file-upload-progress-bar>
          <div class="flex flex-col justify-center rounded-full overflow-hidden bg-primary text-xs text-primary-foreground text-center whitespace-nowrap transition-all duration-500 hs-file-upload-complete:bg-green-500" style="width: 0" data-hs-file-upload-progress-bar-pane></div>
        </div>
      </div>
    </template>

    <div class="cursor-pointer p-12 flex justify-center bg-layer border border-dashed border-layer-line rounded-xl" data-hs-file-upload-trigger>
      <div class="text-center">
        <span class="inline-flex justify-center items-center size-16">
          <svg class="shrink-0 w-16 text-primary mx-auto" width="73" height="47" viewBox="0 0 73 47" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M54.519 40.4773V6.76876C54.519 3.92686 52.2121 1.62305 49.3664 1.62305H22.7076C19.8619 1.62305 17.555 3.92686 17.555 6.76876V40.4773M54.519 40.4773C54.519 43.3192 52.2121 45.6231 49.3664 45.6231H22.7076C19.8619 45.6231 17.555 43.3192 17.555 40.4773M54.519 40.4773L54.5189 34.6563L48.6564 28.3566L43.2612 34.6373C42.4421 35.5908 40.9662 35.5955 40.141 34.6472L30.3406 23.3844L17.555 36.9154V40.4773M6.20483 9.59424L17.707 7.6798V42.5188L12.6457 43.5828C9.25658 44.2954 5.94238 42.0892 5.29702 38.691L1.14643 16.8357C0.500082 13.4322 2.78322 10.1637 6.20483 9.59424ZM65.8691 9.59424L54.3669 7.6798V42.5188L59.4282 43.5828C62.8173 44.2954 66.1316 42.0892 66.7769 38.691L70.9274 16.8357C71.5738 13.4322 69.2907 10.1637 65.8691 9.59424ZM45.0584 15.3561C45.0584 17.7228 43.1372 19.6413 40.7673 19.6413C38.3974 19.6413 36.4762 17.7228 36.4762 15.3561C36.4762 12.9894 38.3974 11.0708 40.7673 11.0708C43.1372 11.0708 45.0584 12.9894 45.0584 15.3561Z" stroke="currentColor" stroke-width="2"/>
          </svg>
        </span>

        <div class="mt-4 flex flex-wrap justify-center text-sm/6 text-muted-foreground-2">
          <span class="pe-1 font-medium text-foreground">
            Glissez et déposez un fichier ici
          </span>
          <span class="bg-layer font-semibold text-primary hover:text-primary-hover rounded-lg decoration-2 hover:underline focus-within:outline-hidden focus-within:ring-2 focus-within:ring-primary-focus focus-within:ring-offset-2">browse</span>
        </div>

        <p class="mt-1 text-xs text-muted-foreground">
          Taille maximale : 2MB
        </p>
      </div>
    </div>

    <div class="grid grid-cols-4 gap-x-2 empty:gap-0" data-hs-file-upload-previews></div>
  </div>
  <!-- End File Upload -->
</template>
