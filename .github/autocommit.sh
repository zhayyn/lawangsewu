#!/bin/bash

# Add all new and modified files to the staging area
git add .

# Generate a commit message using aicommits
# The -g flag generates the message and aicommits exits
COMMIT_MESSAGE=$(aicommits -g 1)

# Commit the changes
git commit -m "$COMMIT_MESSAGE"
