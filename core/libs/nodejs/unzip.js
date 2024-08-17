/**
 * This script extracts files from a specified archive to a destination directory.
 * It uses the `node-7z-archive` module for extraction and `cli-progress` for displaying progress.
 *
 * Command-line arguments:
 * - filePath: The path to the archive file to be extracted.
 * - destination: The directory where the files will be extracted.
 *
 * Example usage:
 * node cli.js /path/to/archive.7z /path/to/destination
 *
 * The script logs progress and errors in JSON format for better integration with other tools.
 */
const path = require('path');
const fs = require('fs');
const cliProgress = require('cli-progress');

const filePath = process.argv[2];
const destination = process.argv[3];

if (!filePath || !destination) {
    console.error(JSON.stringify({error: 'File path and destination must be provided as command-line arguments.'}));
    process.exit(1);
}

console.log(JSON.stringify({message: `Extracting file from ${filePath} to ${destination}`}));

(async () => {
    try {
        const {fullArchive} = await import('node-7z-archive');

        // Create a new progress bar instance
        const progressBar = new cliProgress.SingleBar({}, cliProgress.Presets.shades_classic);
        let totalFiles = 0;
        let lastExtractedFiles = 0;

        fullArchive(filePath, destination)
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
                    console.log(JSON.stringify({progress: progress}));

                    // Additional logging to diagnose issues
                    console.log(`Total files: ${totalFiles}, Extracted files: ${extractedFiles}`);

                    // Flush the output to ensure it is displayed immediately
                    process.stdout.write('\n');
                }
            })
            .then(() => {
                progressBar.update(totalFiles); // Ensure progress bar reaches 100%
                progressBar.stop();
                console.log(JSON.stringify({success: true}));
                // Verify the contents of the destination directory
                fs.readdir(destination, (err, files) => {
                    if (err) {
                        console.error(JSON.stringify({error: `Failed to read destination directory: ${err.message}`}));
                    } else {
                        console.log(JSON.stringify({message: 'Files in destination directory:', files}));
                    }
                });
            })
            .catch((err) => {
                progressBar.stop();
                console.error(JSON.stringify({error: err.message}));
                process.exit(1); // Ensure the process exits with an error code
            });
    } catch (err) {
        console.error(JSON.stringify({error: `Failed to import module: ${err.message}`}));
        process.exit(1);
    }
})();