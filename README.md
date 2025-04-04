# Create Custom Chatbot Avatar WordPress Plugin

This project provides a web application that allows Christine's clients to generate a customized WordPress AI chatbot avatar plugin for their websites. Each client can personalize the chatbot plugin by inputting their own **Knowledge Base**, **Predefined Questions**, **Speaking and Idle Avatar Selection**, and **Language Preference**. The web application then generates a `chatbot.zip` file tailored to the client's needs, which can be deployed directly into their WordPress site.

## Features

- **Knowledge Base Customization**: Clients can input specific knowledge related to their business or company for chatbot responses.
- **Predefined Questions**: Allows input of frequently asked questions for quick and automated responses.
- **Speaking and Idle Avatar Selection**: Clients can choose from a set of predefined avatars or upload their own images for the chatbot's speaking and idle states.
- **Language Preference**: Supports English and Canadian French. The chatbot's prompts and text-to-speech features will adapt based on the specified language in the shortcode.
- **Custom Plugin Generation**: Automatically creates a customized `chatbot.zip` plugin for WordPress.
- **Shortcode-Based Deployment**: Clients can manually specify the language using a shortcode and add the chatbot to specific pages.

---

## Getting Started

### Without Docker

1. Clone the repository:
   ```bash
   git clone https://github.com/Neilsmahajan/create-custom-chatbot-avatar-wordpress-plugin.git
   cd create-custom-chatbot-avatar-wordpress-plugin
   ```
2. Install Composer dependencies:
   ```bash
   (cd backend && composer install)
   ```
3. Start the PHP server:
   ```bash
   php -S localhost:8080
   ```
4. Open your browser at:
   [http://localhost:8080](http://localhost:8080)

### Using Docker

1. Build the Docker image:
   ```bash
   docker build . -t neilsmahajan/cccawp:1.0
   ```
2. Run the container mapping local port 8080 to the container:
   ```bash
   docker run -p 8080:8080 neilsmahajan/cccawp:1.0
   ```
3. Open your browser at:
   [http://localhost:8080](http://localhost:8080)

---

## Project Structure

```
create-custom-chatbot-avatar-wordpress-plugin/
├── backend/
│   ├── vendor/
│   │   └── ...
│   ├── chatbot_template.php
│   ├── composer.json
│   ├── composer.lock
│   └── generate-plugin.php
├── images/
│   └── ... (avatar images and other images)
├── favicon.ico
├── index.html
├── instructions_fr.html
├── instructions.html
├── generated-plugins/
│   └── chatbot.zip
├── .dockerignore
├── .gitignore
├── custom-php.ini
├── Dockerfile
└── README.md
```

---

## Usage

1. Open the application in your browser by going to [http://localhost:8080](http://localhost:8080).
2. Fill in the fields for:
   - **Knowledge Base:** Enter the specific details of your business or organization.
   - **Predefined Questions:** Provide frequently asked questions for the chatbot to address.
   - **Speaking and Idle Avatar Selection:** Choose predefined avatars or upload your own images for the chatbot's speaking and idle states.
3. Click the Submit button.
4. Download the generated `chatbot.zip` file from the provided link.

---

## Deploying the Chatbot Plugin to WordPress

1. Go to your WordPress admin dashboard (`wp-admin`).
2. Navigate to **Plugins** > **Add New**.
3. Click **Upload Plugin**.
4. Click **Choose File**, select the `chatbot.zip` file that you downloaded, and click **Open**.
5. Click **Install Now**.
6. Once the plugin is installed, click **Activate Plugin**.

---

## Adding the Chatbot to WordPress Pages

### Shortcode Example

1. Go to **Pages** in the WordPress admin dashboard.
2. Select **Edit** for the page you want to add the chatbot to.
3. Click the **plus button** to add a new block.
4. Scroll down or search for and click **Shortcode**.
5. Add the shortcode for the chatbot (include the brackets):
   - For English: `[chatbot_avatar language="en-US"]`
   - For Canadian French: `[chatbot_avatar language="fr-CA"]`
6. Click **Save**.
7. Click **View site** to see the chatbot on your page.

---

## Example Workflow

### For a Real Estate Client:

1. **Knowledge Base:**
   - **English:**
     ```
     You are a helpful assistant for a real estate business called Neil's Test Real Estate. Currently Neil's Test Real Estate operates in Scottsdale and has four homes, two of which are for sale.
     ```
   - **Canadian French:**
     ```
     Vous êtes un assistant de confiance pour une entreprise immobilière appelée Neil's Test Real Estate. Actuellement, Neil's Test Real Estate opère à Scottsdale et a quatre maisons, dont deux sont à vendre.
     ```
2. **Predefined Questions:**

   - **English:**

     ```
     Question: What are my Hours of Operation?
     Answer: 9 AM to 5 PM Monday through Thursday, 9 AM to 3 PM Friday.

     Question: What are the current real estate trends in the city?
     Answer: The market is hot, with prices increasing by 10% over the past year.

     Question: How can I find the perfect home for my family?
     Answer: You can search for homes in your price range, and you can also search for homes by location.
     ```

   - **Canadian French:**

     ```
     Question : Quelles sont mes heures d'ouverture ?
     Réponse : De 9 h à 17 h du lundi au jeudi, de 9 h à 15 h le vendredi.

     Question : Quelles sont les tendances immobilières actuelles dans la ville ?
     Réponse : Le marché est en pleine effervescence, les prix ayant augmenté de 10 % au cours de l'année écoulée.

     Question : Comment puis-je trouver la maison idéale pour ma famille ?
     Réponse : Vous pouvez rechercher des maisons dans votre gamme de prix, et vous pouvez également rechercher des maisons par emplacement.
     ```

3. **Speaking and Idle Avatar Selection:** Upload custom images of a professional real estate agent for both speaking and idle states.
4. **Language Preference:** Use the appropriate shortcode (`[chatbot_avatar language="en-US"]` for English or `[chatbot_avatar language="fr-CA"]` for French).
5. **Owner Email:** Provide your email address to receive the emails of users who interact with the chatbot.

The generated plugin will contain these customizations and provide a tailored experience on their WordPress site.

---

## Technical Details

- **Frontend:** HTML, CSS, and JavaScript are used for the user interface, with a focus on simplicity and user-friendliness.
- **Backend:** PHP scripts handle data processing, avatar uploads, and dynamic plugin generation.
- **WordPress Integration:** The generated `chatbot.php` file uses the OpenAI ChatGPT API and Google Cloud Text-to-Speech API for chatbot and voice interactions.

### Template Placeholders in chatbot.php

The backend replaces these placeholders in the template with the client's input:

- {{KNOWLEDGE_BASE}}: The knowledge base provided by the client.
- {{PREDEFINED_QUESTIONS}}: The predefined questions provided by the client.
- {{SPEAKING_AVATAR_FILENAME}}: The speaking avatar image provided by the client.
- {{IDLE_AVATAR_FILENAME}}: The idle avatar image provided by the client.

---

## Development Commands

### Git Branch Management

- **Create a New Branch:**
  ```bash
  git checkout -b feature-name
  ```
- **Push a Branch to Remote:**
  ```bash
  git push -u origin feature-name
  ```
- **Merge a Branch into Main:**
  ```bash
  git checkout main
  git merge feature-name
  git push origin main
  ```

### Start Local Development:

```bash
php -S localhost:8000
```

---

## Future Enhancements

- **User Authentication:** Add user accounts for each client to save their preferences for future updates.
- **Dynamic Multi-Language Support:** Detect the website language dynamically and load the chatbot accordingly.
- **Customization Options:** Allow customization of text color, button styles, and chatbot positioning.
- **Improved UI/UX:** Enhance the design with real-time previews and interactivity.

---

## Contact

For questions or feedback, please contact **Neil Mahajan** at neilsmahajan@gmail.com or nsmahaj1@asu.edu.<br/>
Other team members:

- **Pranav Vad:** pvad1@asu.edu
- **Abbhijit Venkatachalam:** avenka97@asu.edu
- **Sai Kolla:** slkolla@asu.edu

---

Thank you for using the **Create Custom Chatbot Avatar WordPress Plugin!** 🎉
