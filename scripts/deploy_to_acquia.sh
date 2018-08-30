#!/bin/bash
git remote add acquia $ACQUIA_REPO
git push acquia ${CIRCLE_BRANCH} --force
