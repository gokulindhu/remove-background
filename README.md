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
- Add the FileController.php in Controller folder
- Add the route in web.php file or api.php file
- Add the remove_bg.py file in storage folder

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
- FileConntroller.php
  ```php
  <?php

    namespace App\Http\Controllers;

    use App\Http\Controllers\Controller;
    use Illuminate\Support\Facades\Storage;
    use App\Models\Candidate;
    use Illuminate\Http\Request;

    class FileController extends Controller
    {

        public function index(Request $request)
        {
            $candidate_info = Candidate::find($request->input('id'));
            if ($candidate_info == null) {
                if ($request->expectsJson()) {
                    return response()->json(['error' => 'Invalid candidate information provided'], 400);
                }
                return back()->withInput()->withErrors($validation->messages());
            }

            return $this->remove_background($candidate_info);
        }

        public function remove_background($candidate_info)
        {

            try {
                $ext = $this->getUrlExtension($candidate_info->photo_copy_url);
                $name = date('Ymdhis') . '_profile_pic_' . $request->input('candidate_id') . '.' . $ext;
                $finalImagePath = storage_path('app/public/' . $name);
                // Run Python script
                $python = "XDG_CACHE_HOME=/tmp  /usr/bin/python3 ";
                $command = $python . storage_path('remove_bg.py') . " '" . $candidate_info->photo_copy_url . "' '" . $finalImagePath . "'";
                shell_exec($command . " 2>&1"); // Capture errors as well
                Storage::disk(config('filesystems.default'))->put($name, file_get_contents($finalImagePath), [
                    'visibility' => 'public', // Optional: You can set the file to be publicly accessible
                    'Content-Type' => 'application/pdf', // Explicitly set the content type to PDF
                ]);
                unlink($finalImagePath);
                $image_url = Storage::disk(config('filesystems.default'))->url($name);
                if ($request->expectsJson()) {
                    return response()->json(['success' => 'Background removed successfully', 'image_url' => $image_url]);
                }
                return back()->withInput()->withErrors(['success' => 'Background removed successfully']);
            } catch (\Exception $e) {
                if ($request->expectsJson()) {
                    return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
                }
                return back()->withInput()->withErrors(['error' => 'An error occurred: ' . $e->getMessage()]);
            }
        }
    }
  ```
- remove_bg.py
  ```python
  import sys
  import requests
  from rembg import remove
  from io import BytesIO

  def remove_background_from_url(image_url: str, output_path: str):
      # Fetch the image from the URL
      response = requests.get(image_url)
      
      # Ensure the request was successful
      if response.status_code != 200:
          print(f"Error: Unable to fetch image. HTTP Status Code: {response.status_code}")
          return
      
      # Process the image to remove the background
      image_data = BytesIO(response.content)
      output_data = remove(image_data.read())

      # Save the processed image to the output path
      with open(output_path, 'wb') as output_file:
          output_file.write(output_data)
      
      print(f"Background removed and saved to {output_path}")

  if __name__ == "__main__":
      # Check if the right number of arguments were passed
      if len(sys.argv) != 3:
          print("Usage: python script.py <image_url> <output_path>")
          sys.exit(1)
      
      # Get the input parameters from the command line
      image_url = sys.argv[1]
      output_path = sys.argv[2]

      # Call the function to remove the background
      remove_background_from_url(image_url, output_path)

  ```
## License
MIT License