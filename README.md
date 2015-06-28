# aws-ses-php


## What

* This file is a simple set of procedural helpers to interact with Amazon's [Simple Email Service](http://docs.aws.amazon.com/ses/latest/APIReference/Welcome.html) using the Amazon Web Services [PHP SDK](https://github.com/aws/aws-sdk-php).
* It gives the ability to easily send email, check your SES rate limits, and (based off an Amazon SES blog post linked to in the code) handle AWS throttling errors gracefully.

## Documentation
* While normally I would provide my own documentation for the repository, the code instead has comments linking to the documentation provided by Amazon that I used while writing it.
* I think doing so provides much better documentation than I could give, as their documentation naturally stays up to date, contains all the gotchas, and you can easily determine where and how you would like to deviate from my structure.
  * One good deviation would be to write this configuration in an object-oriented manner. I think that's a valid point, and will revisit doing so in the future. However for now, the procedural style is fairly clean and works well. It really depends on how you would be using this repository.

## Why
* My functions are largely just [slightly-]opinionated wrappers around Amazon's SDK. I am publicizing this repository because there is no reason to keep it closed source.
* Most importantly, there was no explanation of how to uses SES via the PHP SDK provided by Amazon (no complaints, it didn't take long to put the pieces together), so I thought it would be helpful to others to see a working configuration.

## Contributions
* Unlike other projects of mine on GitHub, I am more intended on keeping this repository opinionated (see `Why` above).
* However, [pull requests](http://git-scm.com/book/en/v2/GitHub-Contributing-to-a-Project) are still very welcome!
* And of course, please feel free to report issues in the [issue tracker](https://github.com/genkimarshall/reddit-oauth-php/issues).
