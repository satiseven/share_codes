import { DateTime } from 'luxon'
import { BaseModel, BelongsTo, belongsTo, column } from '@ioc:Adonis/Lucid/Orm'
import Team from './Team'

export default class TeamInvite extends BaseModel {
  @column({ isPrimary: true })
  public id: string

  @column()
  public email: string

  @column()
  public teamId: string

  @column()
  public isDeleted: boolean = false

  @column()
  public isAccepted: boolean = false

  @belongsTo(() => Team)
  public team: BelongsTo<typeof Team>

  @column.dateTime({ autoCreate: true })
  public createdAt: DateTime

  @column.dateTime({ autoCreate: true, autoUpdate: true })
  public updatedAt: DateTime

  public get serializedTeamInviteInfo() {
    const { id, email, teamId, isAccepted, isDeleted } = this

    return {
      id,
      email,
      teamId,
      isAccepted,
      isDeleted,
    }
  }
}
