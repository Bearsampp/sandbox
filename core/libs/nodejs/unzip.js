const path = require('path');
const fs = require('fs');
const cliProgress = require('cli-progress');

const filePath = process.argv[2];
const destination = process.argv[3];

if (!filePath || !destination) {
  console.error(JSON.stringify({ error: 'File path and destination must be provided as command-line arguments.' }));
  process.exit(1);
}

console.log(JSON.stringify({ message: `Extracting file from ${filePath} to ${destination}` }));

(async () => {
  try {
    const { fullArchive } = await import('node-7z-archive');

    // Create a new progress bar instance
    const progressBar = new cliProgress.SingleBar({}, cliProgress.Presets.shades_classic);
    let totalFiles = 0;
    let lastExtractedFiles = 0;

    fullArchive(filePath, destination) // Replace with actual password or options
      .progress((files) => {
        if (totalFiles === 0) {
          totalFiles = files.length;
          progressBar.start(totalFiles, 0);
        }
        const extractedFiles = totalFiles - files.length;
        const progress = Math.round((extractedFiles / totalFiles) * 100);

        // Ensure progress is only updated if it increases
        if (extractedFiles > lastExtractedFiles) {
          lastExtractedFiles = extractedFiles;
          progressBar.update(extractedFiles);
          console.log(JSON.stringify({ progress: progress }));

          // Additional logging to diagnose issues
          console.log(`Total files: ${totalFiles}, Extracted files: ${extractedFiles}`);
        }

        // Flush the output to ensure it is displayed immediately
        if (process.stdout.isTTY) {
          process.stdout.write('');
        }
      })
      .then(() => {
        progressBar.update(totalFiles); // Ensure progress bar reaches 100%
        progressBar.stop();
        console.log(JSON.stringify({ success: true }));
        // Verify the contents of the destination directory
        fs.readdir(destination, (err, files) => {
          if (err) {
            console.error(JSON.stringify({ error: `Failed to read destination directory: ${err.message}` }));
          } else {
            console.log(JSON.stringify({ message: 'Files in destination directory:', files }));
          }
        });
      })
      .catch((err) => {
        progressBar.stop();
        console.error(JSON.stringify({ error: err.message }));
        process.exit(1); // Ensure the process exits with an error code
      });
  } catch (err) {
    console.error(JSON.stringify({ error: `Failed to import module: ${err.message}` }));
    process.exit(1);
  }
})();

// Additional error handling for process exit
process.on('uncaughtException', (err) => {
  console.error(JSON.stringify({ error: `Uncaught Exception: ${err.message}` }));
  process.exit(1);
});

process.on('unhandledRejection', (reason, promise) => {
  console.error(JSON.stringify({ error: `Unhandled Rejection: ${reason}` }));
  process.exit(1);
});
