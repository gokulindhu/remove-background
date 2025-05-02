##Background Removal for Images

handles the process of removing the background from a candidate's profile image using a Python script and storing the final output in the default Laravel filesystem.

## Features

- Retrieves a candidate record by ID.
- Invokes a Python script to remove the background from the candidate's photo.
- Saves the processed image to Laravel's storage (`storage/app/public`).
- Returns a JSON response or redirects back with status messages depending on request type.

## Requirements

- Laravel 11+
- Python 3 installed on the server
- `remove_bg.py` script located at `storage_path('remove_bg.py')`
- Writable storage path at `storage/app/public`
- Python dependencies (within `remove_bg.py`)

## Endpoints

### `GET /file`

**Query Parameters:**

- `id` (integer): Candidate ID

**Example:**

```http
GET /file?id=1
Accept: application/json
```

**Returns (on success):**

```json
{
  "success": "Background removed successfully",
  "image_url": "http://yourdomain.com/storage/20250502_profile_pic_1.jpg"
}
```

## Controller Methods

### `index(Request $request)`

- Fetches the candidate record using the provided ID.
- Validates existence.
- Invokes `remove_background()`.

### `remove_background($candidate_info)`

- Determines the file extension of the candidate's image.
- Constructs a filename with a timestamp.
- Calls a Python script to remove the background.
- Saves the processed image to Laravel storage.
- Returns a response depending on whether the request expects JSON.

## Notes

- Make sure the `remove_bg.py` script accepts two arguments:
  1. Image source URL
  2. Final output path

- The `photo_copy_url` must be a publicly accessible image URL or a valid local file path readable by the Python script.

## Troubleshooting

- Ensure the Python script has execution permissions:
  ```bash
  chmod +x storage/remove_bg.py
  ```

- Logs and shell errors are silently ignored by `shell_exec()`. Consider logging the output if debugging:
  ```php
  $output = shell_exec($command . " 2>&1");
  Log::info($output);
  ```

## License
MIT License