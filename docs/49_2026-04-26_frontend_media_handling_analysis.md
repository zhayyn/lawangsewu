# Frontend WaCaraka Media Handling - Comprehensive Analysis

**Document Date:** April 21, 2026

## Executive Summary

The frontend implements media handling through two main Vue components with different approaches:

1. **Chat.vue** - Internal chat system with client-side image compression
2. **WaCaraka/Index.vue** - WhatsApp integration with base64 data URL transmission

---

## 1. COMPONENTS HANDLING MESSAGE SENDING WITH ATTACHMENTS

### 1.1 Primary Components

#### **[resources/js/Pages/Lawangsewu/Chat.vue](resources/js/Pages/Lawangsewu/Chat.vue)**
- **Purpose:** Internal chat messaging system
- **Attachment Handling:** Client-side compression before upload
- **Key Function:** `sendMessage()` (line 434)
- **Media Input Reference:** `fileInput` (ref - line 35)
- **Form Submission:** Axios POST to `lawangsewu.chat.store` route with FormData

#### **[resources/js/Pages/Lawangsewu/WaCaraka/Index.vue](resources/js/Pages/Lawangsewu/WaCaraka/Index.vue)**
- **Purpose:** WhatsApp Caraka messaging platform integration
- **Attachment Handling:** Base64 data URL transmission
- **Key Functions:**
  - `replyToConversation()` (line 1195)
  - `onMediaFileChange()` (line 387)
- **Media Input Reference:** `mediaInputRef` (ref - line 63)
- **API Call:** Custom `callApi()` method with 'send-media' action

#### **[resources/js/Components/lawangsewu/ChatBubble.vue](resources/js/Components/lawangsewu/ChatBubble.vue)**
- **Purpose:** Display received messages with attachments
- **Displays:** Images, videos, documents
- **Data Structure:** Expects `message.attachment` with `kind`, `url`, `mime`, `original_name`

---

## 2. MEDIA FILE UPLOAD & PREPARATION

### 2.1 Chat.vue Approach (Client-Side Compression)

**File:** [resources/js/Pages/Lawangsewu/Chat.vue](resources/js/Pages/Lawangsewu/Chat.vue)

#### Media Size Constraints
```javascript
const maxAttachmentBytes = 2 * 1024 * 1024;  // 2 MB limit (line 51)
```

#### File Selection Handler - `handleAttachmentChange()` (line 312)
```javascript
const handleAttachmentChange = async (event) => {
    const [selectedFile] = event.target.files || [];
    if (!selectedFile) return;
    
    mediaError.value = '';
    isPreparingAttachment.value = true;

    try {
        let preparedFile = selectedFile;

        // IMAGE COMPRESSION
        if (selectedFile.type.startsWith('image/')) {
            preparedFile = await compressImageIfNeeded(selectedFile);
        } 
        // VIDEO SIZE CHECK
        else if (selectedFile.size > maxAttachmentBytes) {
            throw new Error('Video harus maksimal 2 MB...');
        }

        attachmentFile.value = preparedFile;
        attachmentPreviewUrl.value = URL.createObjectURL(preparedFile);
        attachmentSummary.value = {
            name: preparedFile.name,
            size: formatBytes(preparedFile.size),
            kind: preparedFile.type.startsWith('video/') ? 'Video' : 'Gambar',
            mime: preparedFile.type,
        };
    } catch (error) {
        clearAttachment();
        mediaError.value = error.message;
    } finally {
        isPreparingAttachment.value = false;
    }
};
```

#### Image Compression Logic - `compressImageIfNeeded()` (line 269)
- **Compression Approach:** Canvas-based image scaling + quality reduction
- **Supported Formats:** JPEG, PNG, WebP (converts WebP/PNG → WebP)
- **Algorithm:**
  - 6 scale factors: 1.0, 0.92, 0.84, 0.76, 0.68, 0.6
  - 7 quality levels: 0.9, 0.82, 0.74, 0.66, 0.58, 0.5, 0.42
  - Iterative approach: scales dimensions first, then reduces quality
  - Stops when file ≤ 2 MB
- **Error Handling:** Returns original file if can't compress; throws error if unsupported format
- **Canvas Operations:**
  ```javascript
  const canvas = document.createElement('canvas');
  const context = canvas.getContext('2d');
  canvas.width = Math.max(1, Math.round(image.width * scale));
  canvas.height = Math.max(1, Math.round(image.height * scale));
  context.drawImage(image, 0, 0, width, height);
  const blob = await canvasToBlob(canvas, targetType, quality);
  ```

#### Utility Functions
- **`loadImageElement(file)`** (line 245) - Creates image element from file blob
- **`canvasToBlob(canvas, type, quality)`** (line 262) - Converts canvas to blob
- **`formatBytes(value)`** (line 220) - Formats bytes to readable format (B/KB/MB)

### 2.2 WaCaraka/Index.vue Approach (Base64 Data URL)

**File:** [resources/js/Pages/Lawangsewu/WaCaraka/Index.vue](resources/js/Pages/Lawangsewu/WaCaraka/Index.vue)

#### Media Size Constraints
```javascript
const MAX_MEDIA_FILE_BYTES = 15 * 1024 * 1024;  // 15 MB limit (line 71)
```

#### File Selection Handler - `onMediaFileChange()` (line 387)
```javascript
const onMediaFileChange = async (event) => {
    const file = event?.target?.files?.[0];
    if (!file) return;

    if (file.size > MAX_MEDIA_FILE_BYTES) {
        replyState.value = 'error';
        appendLog('File terlalu besar', {
            maxMb: Math.round(MAX_MEDIA_FILE_BYTES / (1024 * 1024)),
            fileSizeMb: (file.size / (1024 * 1024)).toFixed(2)
        });
        clearMediaAttachment();
        return;
    }

    try {
        // CONVERT TO BASE64 DATA URL
        const dataUrl = await new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = () => resolve(String(reader.result || ''));
            reader.onerror = () => reject(new Error('Gagal membaca file'));
            reader.readAsDataURL(file);  // <-- KEY: readAsDataURL creates base64 data URL
        });

        mediaAttachment.value = {
            name: file.name,
            size: file.size,
            mime: file.type || 'application/octet-stream',
            kind: detectMediaKindFromFile(file),
            dataUrl,  // <-- STORED AS BASE64 DATA URL
        };
    } catch {
        replyState.value = 'error';
        appendLog('Gagal memproses lampiran media');
        clearMediaAttachment();
    }
};
```

#### Media Type Detection - `detectMediaKindFromFile()` (line 367)
```javascript
const detectMediaKindFromFile = (file) => {
    const normalized = String(file?.type || '').toLowerCase();
    const fileName = String(file?.name || '').toLowerCase();
    
    if (normalized === 'image/webp' || fileName.endsWith('.webp')) return 'sticker';
    if (normalized.startsWith('image/')) return 'image';
    if (normalized.startsWith('video/')) return 'video';
    if (normalized.startsWith('audio/')) return 'audio';
    return 'document';
};
```

---

## 3. IMAGE/MEDIA SELECTION & HANDLING LOGIC

### 3.1 File Input Elements

#### Chat.vue File Input (line 705)
```vue
<input
    ref="fileInput"
    type="file"
    accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/quicktime"
    class="hidden"
    @change="handleAttachmentChange"
>
```

#### WaCaraka/Index.vue File Input (line 2054)
```vue
<input
    ref="mediaInputRef"
    type="file"
    class="hidden"
    accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.rar,.7z,.csv,.txt,.json,.xml,.webp,.heic,.heif"
    @change="onMediaFileChange"
/>
```

### 3.2 Media Selection UI Flow

#### Chat.vue Selection Flow
1. **Button Click** → `fileInput?.click()` (line 726)
2. **User Selects File** → `handleAttachmentChange()` triggered
3. **Async Processing:**
   - Show `isPreparingAttachment = true` spinner
   - Compress image if needed
   - Create preview URL with `URL.createObjectURL(file)`
   - Generate `attachmentSummary` for display
4. **Display Preview:**
   - Shows thumbnail with file name & size
   - Offers "Hapus" (delete) button to clear
   - Shows compression progress message
5. **Send:** FormData includes `attachmentFile` when submitted

#### WaCaraka/Index.vue Selection Flow
1. **"+ Media" Button Click** → `pickMediaFile()` (line 385)
   - Validates: has active conversation, can reply, not already sending
2. **User Selects File** → `onMediaFileChange()` triggered
3. **Instant Processing:**
   - Validate file size (≤15 MB)
   - Read as base64 data URL using `FileReader.readAsDataURL()`
   - Store complete data in `mediaAttachment` object
4. **Display Summary:**
   - Shows media kind, file name, size
   - Small inline preview text
5. **Send:** Includes `media.dataUrl` in API call

### 3.3 Preview Functionality

#### Chat.vue Attachment Preview (line 775-800)
- **Image Preview:** Shows in-line thumbnail, clickable to zoom
- **Video Preview:** Inline video player with controls
- **Modal Preview:** Full-screen image zoom modal (lines 877-898)
  ```vue
  <div v-if="showAttachmentPreviewModal && attachmentSummary?.kind === 'Gambar' && attachmentPreviewUrl"
       class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4 backdrop-blur-sm">
      <!-- Enlarged image display -->
  </div>
  ```

#### WaCaraka/Index.vue Media Display
- Shows only summary text (kind, name, size)
- No inline preview before sending

---

## 4. MEDIA_URL CONSTRUCTION

### 4.1 Chat.vue - File Upload with FormData

**Sending Method:** [resources/js/Pages/Lawangsewu/Chat.vue](resources/js/Pages/Lawangsewu/Chat.vue#L443)

```javascript
const sendMessage = () => {
    const payload = new FormData();
    const trimmedContent = draftContent.value.trim();

    if (trimmedContent) {
        payload.append('content', trimmedContent);
    }

    if (attachmentFile.value) {
        payload.append('attachment', attachmentFile.value);  // <-- RAW FILE
    }

    window.axios.post(route('lawangsewu.chat.store'), payload, {
        headers: { Accept: 'application/json' },
        onUploadProgress: (event) => {
            if (!event.total) return;
            uploadProgress.value = Math.round((event.loaded * 100) / event.total);
        },
    }).then((response) => {
        const message = response?.data?.data;
        if (message) {
            upsertMessage({
                metadata: message.attachment ? { attachment: message.attachment } : null,
            });
        }
    }).catch((error) => {
        mediaError.value = error?.response?.data?.errors?.attachment?.[0] || ...;
    });
};
```

**Response Structure:** Backend returns `message.attachment` with:
- `kind` - attachment type (image, video, document)
- `url` - **Server-generated URL path** to stored file (e.g., `/storage/attachments/...`)
- `mime` - MIME type
- `original_name` - Original file name

### 4.2 WaCaraka/Index.vue - Base64 Data URL Transmission

**Sending Method:** [resources/js/Pages/Lawangsewu/WaCaraka/Index.vue](resources/js/Pages/Lawangsewu/WaCaraka/Index.vue#L1195)

```javascript
const replyToConversation = async () => {
    const text = replyText.value.trim();
    const media = mediaAttachment.value;
    
    if (media) {
        const result = await callApi('send-media', {
            method: 'post',
            data: {
                conversation_id: activeConvoId.value,
                media_kind: media.kind,
                media_url: media.dataUrl,  // <-- BASE64 DATA URL
                mime_type: media.mime,
                file_name: media.name,
                caption: text || null,
                ptt: media.kind === 'audio',
            },
        });
    } else {
        // Text-only reply
        await callApi('reply', {
            method: 'post',
            data: { conversation_id: activeConvoId.value, text }
        });
    }
};
```

**Data URL Format:**
- Example: `data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEA...` (complete base64-encoded file)
- Size: Scales with file size (15 MB max = ~20 MB base64 string)
- Transmission: Sent via JSON POST body

**API Endpoint:** `route('admin.wacaraka.api', { action: 'send-media' })`

---

## 5. VALIDATION & ERROR HANDLING

### 5.1 Chat.vue Validation

#### File Type Validation
```javascript
accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/quicktime"
```
- Client-side: HTML accept attribute
- Server-side: Laravel validation (implied by error handling)

#### File Size Validation
```javascript
if (file.size <= maxAttachmentBytes) {  // 2 MB
    return file;
} else if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
    throw new Error('File gambar ini masih di atas 2 MB dan belum bisa dikompres otomatis...');
}
```

#### Compression Validation
- If can't compress below 2 MB → throws error
- If supported format → automatically compresses
- If unsupported format → error message to user

#### Server Response Errors (line 493-497)
```javascript
.catch((error) => {
    const errors = error?.response?.data?.errors || {};
    mediaError.value = errors.attachment?.[0] 
        || (!attachmentFile.value ? errors.content?.[0] : '')
        || error?.response?.data?.message 
        || 'Gagal mengirim pesan.';
    pushToast(mediaError.value, 'error');
});
```

### 5.2 WaCaraka/Index.vue Validation

#### File Size Validation
```javascript
if (file.size > MAX_MEDIA_FILE_BYTES) {  // 15 MB
    replyState.value = 'error';
    appendLog('File terlalu besar', {
        maxMb: Math.round(MAX_MEDIA_FILE_BYTES / (1024 * 1024)),
        fileSizeMb: (file.size / (1024 * 1024)).toFixed(2)
    });
    clearMediaAttachment();
    return;
}
```

#### File Type Validation
```javascript
accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.rar,.7z,.csv,.txt,.json,.xml,.webp,.heic,.heif"
```

#### FileReader Errors (line 406-423)
```javascript
try {
    const dataUrl = await new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(String(reader.result || ''));
        reader.onerror = () => reject(new Error('Gagal membaca file'));
        reader.readAsDataURL(file);
    });
} catch {
    replyState.value = 'error';
    appendLog('Gagal memproses lampiran media');
    scheduleReplyStateReset(2200);
    clearMediaAttachment();
}
```

#### API Errors
- Caught by `callApi()` wrapper
- Logged via `appendLog()` system
- State reset after 2.2 seconds

---

## 6. DISPLAY & RENDERING

### 6.1 ChatBubble.vue Message Rendering

**File:** [resources/js/Components/lawangsewu/ChatBubble.vue](resources/js/Components/lawangsewu/ChatBubble.vue#L164)

#### Image Display (line 166-177)
```vue
<a v-if="message.attachment.kind === 'image'"
   :href="message.attachment.url"
   target="_blank"
   rel="noreferrer"
   class="block overflow-hidden rounded-2xl border border-black/5">
    <img :src="message.attachment.url"
         :alt="message.attachment.original_name || 'Lampiran gambar chat'"
         class="max-h-80 w-full object-cover"
         loading="lazy" />
</a>
```

#### Video Display (line 181-189)
```vue
<video v-else-if="message.attachment.kind === 'video'"
       class="w-full rounded-2xl border border-black/5 bg-black"
       controls playsinline preload="metadata">
    <source :src="message.attachment.url" :type="message.attachment.mime" />
</video>
```

#### Document/Other File Display (line 191-199)
```vue
<a :href="message.attachment.url"
   target="_blank"
   rel="noreferrer"
   class="inline-flex items-center gap-2 text-[11px] font-semibold underline">
    {{ message.attachment.original_name || 'Buka lampiran' }}
</a>
```

### 6.2 Chat.vue Attachment Summary Display

**Location:** [resources/js/Pages/Lawangsewu/Chat.vue](resources/js/Pages/Lawangsewu/Chat.vue#L756)

```vue
<div v-if="attachmentSummary" 
     class="flex items-center justify-between gap-3 rounded-2xl border border-[var(--accent-border)] bg-[var(--accent-soft)] px-3 py-2">
    <div class="min-w-0">
        <p class="truncate font-bold text-[var(--text-1)]">{{ attachmentSummary.kind }} siap dikirim</p>
        <p class="truncate text-[var(--text-2)]">{{ attachmentSummary.name }} · {{ attachmentSummary.size }}</p>
    </div>
    <button @click="clearAttachment">Hapus</button>
</div>
```

---

## 7. DATA STRUCTURES & OBJECT SHAPES

### 7.1 mediaAttachment (WaCaraka)
```javascript
{
    name: string,           // "photo.jpg"
    size: number,           // 12345678
    mime: string,           // "image/jpeg"
    kind: 'image' | 'video' | 'audio' | 'sticker' | 'document',
    dataUrl: string         // "data:image/jpeg;base64,..."
}
```

### 7.2 attachmentFile (Chat)
```javascript
// File object directly
File {
    name: string,
    size: number,
    type: string,           // MIME type
    lastModified: number
}
```

### 7.3 attachmentSummary (Chat)
```javascript
{
    name: string,           // "photo.jpg"
    size: string,           // "1.2 MB"
    kind: 'Gambar' | 'Video',
    mime: string            // "image/jpeg"
}
```

### 7.4 message.attachment (Response)
```javascript
{
    kind: 'image' | 'video' | 'document',
    url: string,            // "/storage/attachments/xxxxx"
    mime: string,           // "image/jpeg"
    original_name: string   // "photo.jpg"
}
```

---

## 8. KEY FILES SUMMARY

| File | Type | Purpose | Key Functions |
|------|------|---------|---|
| [Chat.vue](resources/js/Pages/Lawangsewu/Chat.vue) | Page Component | Internal team chat with image compression | `sendMessage()`, `handleAttachmentChange()`, `compressImageIfNeeded()` |
| [WaCaraka/Index.vue](resources/js/Pages/Lawangsewu/WaCaraka/Index.vue) | Page Component | WhatsApp messaging with base64 transmission | `replyToConversation()`, `onMediaFileChange()`, `detectMediaKindFromFile()` |
| [ChatBubble.vue](resources/js/Components/lawangsewu/ChatBubble.vue) | Display Component | Renders messages with attachments | Default export |
| [Admin/WaCarakaManager.vue](resources/js/Pages/Admin/WaCarakaManager.vue) | Admin Page | Device management, no media sending | `sendMessage()` (text-only), `sendBroadcast()` |

---

## 9. TECHNICAL SPECIFICATIONS

### 9.1 Size Limits
| Component | Max Size | Format |
|-----------|----------|--------|
| Chat.vue | 2 MB | Compressed with auto-resize |
| WaCaraka/Index.vue | 15 MB | Base64 data URL |

### 9.2 Supported MIME Types

**Chat.vue:**
- Images: JPEG, PNG, WebP, GIF
- Videos: MP4, WebM, QuickTime

**WaCaraka/Index.vue:**
- Images: All formats
- Videos: All formats
- Audio: All formats
- Documents: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, ZIP, RAR, 7Z, CSV, TXT, JSON, XML, WEBP, HEIC, HEIF

### 9.3 Transmission Methods

| System | Method | Encoding | Max Payload |
|--------|--------|----------|-------------|
| Chat.vue | FormData (Multipart) | Binary | 2 MB + text |
| WaCaraka/Index.vue | JSON POST | Base64 | ~20 MB (15 MB base64) |

### 9.4 Progress Tracking

**Chat.vue:** 
- `uploadProgress` ref (0-100%)
- Updated via Axios `onUploadProgress` callback
- Shows percentage during upload

**WaCaraka/Index.vue:**
- No explicit progress tracking
- Uses `replyState` for status (idle/sending/sent/error)

---

## 10. RECOMMENDATIONS FOR ENHANCEMENT

Based on current implementation analysis:

1. **WaCaraka Base64 Issue:** Sending 15 MB+ base64 strings in JSON is inefficient. Consider:
   - Switch to FormData like Chat.vue
   - Implement multipart/form-data upload
   - Add server-side base64 decoding if required

2. **Unified Compression:** Chat.vue compression logic could be extracted to shared utility and used in WaCaraka

3. **Upload Progress:** WaCaraka lacks upload progress feedback - could implement similar to Chat.vue

4. **Preview Generation:** Consider server-side thumbnail generation for efficiency

5. **Error Messages:** More granular error handling with specific user-facing messages

---

## Conclusion

The frontend implements two different media handling approaches optimized for their use cases:
- **Chat.vue** prioritizes efficiency with client-side compression and binary upload
- **WaCaraka/Index.vue** prioritizes simplicity with base64 data URL transmission (suitable for WhatsApp integration layer)

Both use FileReader API for file handling and include proper validation, error handling, and user feedback.
