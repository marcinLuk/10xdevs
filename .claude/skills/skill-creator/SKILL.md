---
name: skill-creator
description: Use this skill to create new skills for claude code
---

Your job is to create new skills for Claude Code.
Read guides and documentation to understand how skills work, do not search any other skills, your job is to help user
create new skill
to extend Claude's capabilities.

Before start ask user couple of questions to understand what kind of skill they want to create and what it should do :

1. What is the purpose of the skill? What do you want it to do?
2. Location: Do you want this skill to be available in all your projects (personal), just this project (project)
3. Should Claude be able to invoke this skill automatically when relevant, or do you want to control when it runs by
   invoking it directly?
4. Do you want to include any reference material or supporting files with the skill, like templates, example outputs, or
   scripts?
5. Do you want to restrict which tools Claude can use when this skill is active?
6. Do you want this skill to run in isolation with its own context (in a subagent), or run inline with the rest of the
   conversation?
7. Do you want to pass any arguments to the skill when invoking it?
8. conversation type with user. There are two main types of conversations: "questionnaire" - where Claude asks user a
   series of questions to gather information (one at time), and "instructional" - where user gives instructions and Claude follows
   them. Which one do you prefer for this skill?

Ask one question at time, wait for user's response before asking the next question.
In each question, provide short (2-3 sentence max) explanation of why this question is relevant and how it affects the
skill's behavior and your recommendation. Explanation and recommendation should be visibly separated from the question
itself, for example:

```
**Explanation:** This question helps determine the scope of the skill and where it will be available.
**Recommendation:** If you want to use this skill across multiple projects, choose "personal". If it's specific to this project, choose "project".
```

Conversation with user should be in Polish, but the skill you create should be in English, as that's the standard for
Claude skills.

<cladueSkillCreationGuide>
@\home\marci\.claude\skills\skill-creator\guide.md
</cladueSkillCreationGuide>

