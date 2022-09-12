import type { HttpContextContract } from '@ioc:Adonis/Core/HttpContext'
import Team from 'App/Models/Team'
import User from 'App/Models/User'
import CreateTeamValidator from 'App/Validators/Team/CreateTeamValidator'
import UpdateTeamValidator from 'App/Validators/Team/UpdateTeamValidator'

export default class TeamController {
  public async show({ auth }: HttpContextContract) {
    const user: User = auth.use('api').user!
    const team = await Team.query().where({ ownerId: user.id }).firstOrFail()

    return {
      data: {
        team: team.serializedTeamInfo,
      },
    }
  }

  public async create({ auth, request }: HttpContextContract) {
    const user: User = auth.use('api').user!
    const { name } = await request.validate(CreateTeamValidator)
    const team = await Team.create({ name, ownerEmail: user.email, ownerId: user.id })

    return {
      data: {
        message: 'Team created successfully',
        team: team.serializedTeamInfo,
      },
    }
  }

  public async update({ auth, request }: HttpContextContract) {
    const user: User = auth.use('api').user!
    const { name } = await request.validate(UpdateTeamValidator)
    const team = await Team.query().where({ ownerId: user.id }).firstOrFail()

    const updatedTeam = await team.merge({ name }).save()

    return {
      data: {
        message: 'Team updated successfully',
        team: updatedTeam.serializedTeamInfo,
      },
    }
  }

  public async destroy({ auth }: HttpContextContract) {
    const user: User = auth.use('api').user!
    const team = await Team.query().where({ userId: user.id }).firstOrFail()

    await team.merge({ isDeleted: true }).save()

    return {
      data: {
        message: 'Team deleted successfully',
      },
    }
  }

  public async stats({ auth }: HttpContextContract) {
    const user: User = auth.use('api').user!
    const team = await Team.query()
      .where({ ownerId: user.id })
      .withCount('teamMembers', (query) => {
        query.as('membersCount')
      })
      .withCount('teamInvites', (query) => {
        query.as('invitesCount')
      })
      .withCount('teamInvites', (query) => {
        query.where({ isAccepted: false }).as('pendingInvitesCount')
      })
      .firstOrFail()

    return {
      data: {
        membersCount: team.$extras.membersCount,
        invitesCount: team.$extras.invitesCount,
        pendingInvitesCount: parseInt(team.$extras.pendingInvitesCount),
      },
    }
  }
}
