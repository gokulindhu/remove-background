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
