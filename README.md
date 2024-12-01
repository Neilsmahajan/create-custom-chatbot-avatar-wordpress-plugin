# Create Custom Chatbot Avatar WordPress Plugin

This project provides a web application that allows Christine's clients to generate a customized WordPress AI chatbot avatar plugin for their websites. Each client can personalize the chatbot plugin by inputting their own **Knowledge Base**, **Predefined Questions**, **Avatar Selection**, and **Language Preference**. The web application then generates a `chatbot.php` file tailored to the client's needs, which can be deployed directly into their WordPress site.

## Features

- **Knowledge Base Customization**: Clients can input specific knowledge related to their business or company for chatbot responses.
- **Predefined Questions**: Allows input of frequently asked questions for quick and automated responses.
- **Avatar Selection**: Clients can choose from a set of predefined avatars or upload their own image for the chatbot.
- **Language Preference**: Supports English and Canadian French. The chatbot's prompts and text-to-speech features will adapt based on the selected language.
- **Custom Plugin Generation**: Automatically creates a customized `chatbot.php` plugin for WordPress.

---

## Getting Started

### Prerequisites

- PHP installed on your system (version 7.4 or higher recommended).
- Basic knowledge of WordPress plugins and PHP.
- A local or hosted web server (e.g., Apache, Nginx, or PHP's built-in server).

### Installation

1. Clone this repository to your local system:
   ```bash
   git clone https://github.com/Neilsmahajan/create-custom-chatbot-avatar-wordpress-plugin.git
   cd create-custom-chatbot-avatar-wordpress-plugin
2. Start the built-in PHP server:
   ``` bash
   php -S localhost:8080
3. Open the application in your browser:
   ``` bash
   http://localhost:8000/frontend/index.html

---

## Folder Structure

- frontend/: Contains the HTML and JavaScript for the user interface of the web app.
- backend/: Contains the PHP scripts to handle form submissions and generate the custom WordPress plugin.
- generated-plugins/: Stores the generated chatbot.php files for each client.
- uploads/: Stores the avatar images uploaded by clients.

---

## Usage

1. Open the application in your browser.
2. Fill in the fields for:
   - Knowledge Base: Enter the specific details of your business or organization.
   - Predefined Questions: Provide frequently asked questions for the chatbot to address.
   - Language Preference: Select either English or Canadian French.
   - Avatar Selection: Choose a predefined avatar or upload your own image.
3. Click the Submit button.
4. Download the generated chatbot.php file from the provided link.
5. Upload the chatbot.php file to the /wp-content/plugins/ folder of your WordPress website.
6. Activate the plugin in the WordPress admin dashboard.

---

## Example Workflow

### For a Real Estate Client:

1. Knowledge Base:
   ```
   "You are a helpful assistant for a real estate business. Provide information about available properties, buying/selling processes, and mortgage options."
2. Predefined Questions:
   ```
   "What are the current real estate trends in the city?"
   "How can I find the perfect home for my family?"
   "What is the process for selling my property?"
   ```
3. Avatar Selection: Upload a custom image of a professional real estate agent.
4. Language Preference: Canadian French

The generated plugin will contain these customizations and provide a tailored experience on their WordPress site.

---

## Technical Details

- **Frontend:** HTML, CSS, and JavaScript are used for the user interface, with a focus on simplicity and user-friendliness.
- **Backend:** PHP scripts handle data processing, avatar uploads, and dynamic plugin generation.
- **WordPress Integration:** The generated chatbot.php file uses the OpenAI ChatGPT API and Google Cloud Text-to-Speech API for chatbot and voice interactions.

### Template Placeholders in chatbot.php

The backend replaces these placeholders in the template with the client's input:
- {{KNOWLEDGE_BASE}}: The knowledge base provided by the client.
- {{PREDEFINED_QUESTIONS}}: The predefined questions provided by the client.
- {{LANGUAGE}}: The language preference provided by the client.
- {{AVATAR}}: The avatar image provided by the client.

---

## Development Commands

- **Initialize Git Repository:**
   ``` bash
   git init
   git add .
   git commit -m "Initial commit"
   git branch -M main
   git remote add origin https://github.com/Neilsmahajan/create-custom-chatbot-avatar-wordpress-plugin.git
   git push -u origin main
   ```
- **Start Local Development:**
   ``` bash
   php -S localhost:8000
   ```

---

## Future Enhancements

- **User Authentication:** Add user accounts for each client to save their preferences for future updates.
- **Multi-Language Support:** Expand language options to include other dialects or languages.
- **Dynamic Preview:** Allow users to preview the chatbot on their website directly within the app.
- **Improved UI/UX:** Enhance the design with more interactive elements and real-time previews.

---

## Contributing

1.	Fork this repository.
2.	Create a feature branch: `git checkout -b feature-name`.
3.	Commit your changes: `git commit -m "Add feature name"`.
4.	Push to the branch: `git push origin feature-name`.
5.	Open a pull request.

---

## Contact

For questions or feedback, please contact **Neil Mahajan** at neilsmahajan@gmail.com or nsmahaj1@asu.edu.<br/>
Other team members:
- **Pranav Vad** - pvad1@asu.edu
- **Abbhijit Venkatachalam** - avenka97@asu.edu
- **Sai Kolla** - slkolla@asu.edu

---

Thank you for using the **Create Custom Chatbot Avatar WordPress Plugin!** 🎉