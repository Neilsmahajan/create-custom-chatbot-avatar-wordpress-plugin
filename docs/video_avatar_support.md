# Video Avatar Support

This document explains how to use video files as speaking avatars in the chatbot.

## Supported Video Formats

The chatbot plugin supports the following video formats for speaking avatars:

- MP4 (.mp4)
- QuickTime (.mov)
- WebM (.webm)

## How to Use Video Avatars

### Method 1: Choose a Predefined Video Avatar

1. On the chatbot customization page, select one of the predefined video avatars in the "Speaking Avatar Selection" section.
2. Video avatars will automatically play when the chatbot is speaking.

### Method 2: Upload Your Own Video Avatar

1. Click the "Upload Speaking Custom Avatar" button
2. Select a video file from your computer (supported formats: .mp4, .mov, .webm)
3. The system will automatically handle the video integration with your chatbot

## Technical Implementation Details

When using a video as a speaking avatar:

1. The plugin will show the idle avatar (image) when the chatbot is not speaking
2. When the chatbot speaks, the plugin will:
   - Hide the idle avatar image
   - Show and play the video avatar
3. When speech ends, the plugin will:
   - Hide the video avatar
   - Show the idle avatar image again
   - Reset the video to the beginning for the next interaction

## Troubleshooting

If your video avatar isn't displaying correctly:

1. Ensure the video format is supported (.mp4, .mov, or .webm)
2. Check that the video file is not corrupted and can play in a standard media player
3. For optimal performance, keep your video file size under 5MB
4. Consider converting very large videos to a more efficient format like WebM
