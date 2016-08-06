###################
# install/update pipeline command
###################

# Load properties file, but pass all credentials during the install/update of the pipeline
fly set-pipeline -p ge-calc-production -c production-pipeline.yml -v "repo-private-key=$(cat id_rsa)" -v "cf-password=pivotal" -v "AWS-key-id=AKIAJGQW3IS4FT5RYFEQ" -v "AWS-secret=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" -l properties.yml
