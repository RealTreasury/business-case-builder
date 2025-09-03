# Debugging JSON Responses

When the wizard submits data, WordPress handles the request at `admin-ajax.php` and returns a JSON response dynamically. No JSON file is saved to disk.

To inspect the response and verify fields like the analysis date:

- **Browser DevTools**
    - Open the Network tab, submit the form, and select the `admin-ajax.php` request.
    - The **Response** panel displays the raw JSON returned by the server.
- **API Logs Page**
    - In the WordPress admin dashboard, go to **Business Case Builder → API Logs**.
    - Each log entry stores the request and response payloads. Click **View** to inspect the saved JSON.

These are the only locations where the JSON exists. If a field such as the analysis date is missing, review the response in one of the above locations to determine whether the server omitted it or if the frontend failed to display it.
